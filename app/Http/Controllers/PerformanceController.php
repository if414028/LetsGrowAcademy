<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $childIds = $user->downlineUserIds();

        $q = trim((string) $request->get('q', ''));

        // date range dari request
        $from = $request->get('from'); // YYYY-MM-DD
        $to   = $request->get('to');

        // ✅ default range: bulan ini -1 bulan s/d +1 bulan (kalau user tidak filter)
        if (!$from && !$to) {
            $from = Carbon::now()->startOfMonth()->subMonth()->toDateString();
            $to   = Carbon::now()->endOfMonth()->addMonth()->toDateString();
        } else {
            // opsional: kalau user cuma isi salah satu
            if ($from && !$to) $to = Carbon::parse($from)->endOfMonth()->toDateString();
            if (!$from && $to) $from = Carbon::parse($to)->startOfMonth()->toDateString();
        }

        // =========================
        // TEAM PERFORMANCE (units selesai)
        // =========================
        $teamPerformance = User::query()
            ->whereIn('users.id', $childIds)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('users.name', 'like', "%{$q}%")
                        ->orWhere('users.full_name', 'like', "%{$q}%");
                });
            })
            ->leftJoin('sales_orders', function ($join) use ($from, $to) {
                $join->on('sales_orders.sales_user_id', '=', 'users.id')
                    ->whereNull('sales_orders.deleted_at')
                    ->where('sales_orders.status', 'selesai'); // ✅ hanya selesai

                // ✅ filter install_date range
                if ($from) $join->whereDate('sales_orders.install_date', '>=', $from);
                if ($to)   $join->whereDate('sales_orders.install_date', '<=', $to);
            })
            ->leftJoin('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('units')
            ->get()
            ->map(function ($u) use ($from, $to) {
                $lastOrderQ = SalesOrder::where('sales_user_id', $u->id)
                    ->whereNull('deleted_at')
                    ->where('status', 'selesai');

                if ($from) $lastOrderQ->whereDate('install_date', '>=', $from);
                if ($to)   $lastOrderQ->whereDate('install_date', '<=', $to);

                $lastOrder = $lastOrderQ->latest('install_date')->first();

                return [
                    'id'            => $u->id,
                    'name'          => $u->name,
                    'units'         => (int) $u->units,
                    'last_activity' => $lastOrder?->order_no ?? 'N/A',
                ];
            });

        // =========================
        // MY TOTAL UNITS (self selesai)
        // =========================
        $myTotalUnitsQ = DB::table('sales_orders')
            ->whereNull('sales_orders.deleted_at')
            ->where('sales_orders.sales_user_id', $user->id)
            ->where('sales_orders.status', 'selesai')
            ->leftJoin('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id');

        if ($from) $myTotalUnitsQ->whereDate('sales_orders.install_date', '>=', $from);
        if ($to)   $myTotalUnitsQ->whereDate('sales_orders.install_date', '<=', $to);

        $myTotalUnits = $myTotalUnitsQ
            ->selectRaw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            ->value('units');

        // =========================
        // SUMMARY CARDS (self + downline)
        // =========================
        $scopeUserIds = $childIds->push($user->id)->unique()->values();

        $summaryQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        // ✅ range pakai key_in_at
        if ($from) $summaryQ->whereDate('so.key_in_at', '>=', $from);
        if ($to)   $summaryQ->whereDate('so.key_in_at', '<=', $to);

        // exclude null key_in_at kalau sedang filter/default range
        if ($from || $to) $summaryQ->whereNotNull('so.key_in_at');

        $summary = $summaryQ->selectRaw("
        -- Total Key-In:
        SUM(
            CASE
                WHEN so.ccp_status = 'menunggu pengecekan'
                 AND (so.status = 'menunggu verifikasi' OR so.status = 'dibatalkan')
                THEN 1 ELSE 0
            END
        ) as total_key_in,

        -- Total Recurring:
        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'menunggu pengecekan'
                 AND so.status = 'menunggu verifikasi'
                THEN 1 ELSE 0
            END
        ) as total_recurring,

        -- Menunggu Jadwal:
        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'disetujui'
                 AND so.status = 'menunggu jadwal'
                THEN 1 ELSE 0
            END
        ) as menunggu_jadwal,

        -- Task ID:
        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'disetujui'
                 AND so.status IN ('dijadwalkan', 'ditunda', 'gagal penelponan')
                THEN 1 ELSE 0
            END
        ) as task_id,

        -- Total Sudah Install:
        SUM(
            CASE
                WHEN so.status = 'selesai'
                THEN 1 ELSE 0
            END
        ) as total_sudah_install
    ")->first();

        // =========================
        // TEAM SHEET (downline)
        // =========================
        $scopeUserIds = $childIds->unique()->values();

        $soiAgg = DB::table('sales_order_items')
            ->selectRaw('sales_order_id, COALESCE(SUM(qty),0) as ns_units')
            ->groupBy('sales_order_id');

        $sheetQ = DB::table('sales_orders as so')
            ->join('users as u', 'u.id', '=', 'so.sales_user_id')
            ->leftJoin('customers as c', function ($j) {
                $j->on('c.id', '=', 'so.customer_id')->whereNull('c.deleted_at');
            })
            ->leftJoinSub($soiAgg, 'soi', function ($j) {
                $j->on('soi.sales_order_id', '=', 'so.id');
            })
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        // filter member
        if ($q !== '') {
            $sheetQ->where(function ($w) use ($q) {
                $w->where('u.name', 'like', "%{$q}%")
                    ->orWhere('u.full_name', 'like', "%{$q}%");
            });
        }

        // date range (KEY IN)
        if ($from) $sheetQ->whereDate('so.key_in_at', '>=', $from);
        if ($to)   $sheetQ->whereDate('so.key_in_at', '<=', $to);

        // exclude null key_in_at kalau sedang filter/default range
        if ($from || $to) $sheetQ->whereNotNull('so.key_in_at');

        $teamSheetRows = $sheetQ
            ->orderBy('u.name')
            ->orderBy('so.key_in_at')
            ->select([
                'so.id',
                'so.sales_user_id',
                DB::raw("COALESCE(NULLIF(u.full_name,''), u.name) as hp_name"),
                DB::raw("COALESCE(c.full_name, '-') as customer_name"),
                'so.key_in_at',
                'so.ccp_status',
                'so.status',
                'so.install_date',
                'so.ccp_remarks',
                DB::raw("CASE WHEN so.ccp_status = 'disetujui' THEN so.updated_at ELSE NULL END as ccp_approved_at"),
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
            ->get()
            ->groupBy('hp_name');

        return view('performances.index', [
            'teamPerformance' => $teamPerformance,
            'teamMemberCount' => $childIds->count(),
            'myTotalUnits'    => (int) $myTotalUnits,
            'q'               => $q,
            'from'            => $from,
            'to'              => $to,
            'summary'         => $summary,
            'teamSheetRows'   => $teamSheetRows,
        ]);
    }

    public function teamDetail(Request $request, User $user)
    {
        $auth = $request->user();

        $isChild = $auth->childrenUsers()->where('users.id', $user->id)->exists();
        abort_unless($isChild, 403);

        $from = $request->get('from');
        $to   = $request->get('to');

        $totalUnitsQ = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->where('so.status', 'selesai'); // ✅

        if ($from) $totalUnitsQ->whereDate('so.install_date', '>=', $from);
        if ($to)   $totalUnitsQ->whereDate('so.install_date', '<=', $to);

        $totalUnits = $totalUnitsQ->sum('soi.qty');

        $ordersQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->where('so.status', 'selesai'); // ✅

        if ($from) $ordersQ->whereDate('so.install_date', '>=', $from);
        if ($to)   $ordersQ->whereDate('so.install_date', '<=', $to);

        $orders = $ordersQ
            ->orderByDesc('so.install_date')
            ->limit(10)
            ->get(['so.id', 'so.order_no', 'so.status', 'so.key_in_at', 'so.install_date']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->full_name ?: $user->name,
            'total_units' => (int) $totalUnits,
            'revenue' => 0,
            'orders' => $orders,
        ]);
    }
}
