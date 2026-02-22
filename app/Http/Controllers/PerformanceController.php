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
        $authUser = $request->user();

        // semua downline auth (untuk list dropdown)
        $authDownlineIds = $authUser->downlineUserIds();

        // ✅ member_id terpilih (optional)
        $memberId = (int) $request->get('member_id', 0);

        // allowed ids = auth + downline auth
        $allowedIds = $authDownlineIds->push($authUser->id)->unique()->values();

        // ✅ base user = auth user (default), atau user terpilih (jika valid)
        $baseUser = $authUser;
        if ($memberId && $allowedIds->contains($memberId)) {
            $baseUser = User::query()->whereKey($memberId)->first() ?? $authUser;
        }

        // ✅ scope = baseUser + seluruh downline baseUser
        $childIds = $baseUser->downlineUserIds(); // downline dari yang dipilih
        $scopeUserIds = $childIds->push($baseUser->id)->unique()->values();

        // keyword (kita tidak pakai lagi untuk filtering di query, karena sudah pakai dropdown)
        $q = trim((string) $request->get('q', ''));

        // date range dari request
        $from = $request->get('from'); // YYYY-MM-DD
        $to   = $request->get('to');

        // ✅ default range: bulan ini -1 bulan s/d +1 bulan (kalau user tidak filter)
        if (!$from && !$to) {
            $from = Carbon::now()->startOfMonth()->subMonth()->toDateString();
            $to   = Carbon::now()->endOfMonth()->addMonth()->toDateString();
        } else {
            if ($from && !$to) $to = Carbon::parse($from)->endOfMonth()->toDateString();
            if (!$from && $to) $from = Carbon::parse($to)->startOfMonth()->toDateString();
        }

        // ✅ dropdown options: auth user + semua downline auth
        $memberOptions = User::query()
            ->whereIn('id', $allowedIds)
            ->orderByRaw("COALESCE(NULLIF(full_name,''), name) asc")
            ->get(['id', 'name', 'full_name'])
            ->map(fn($u) => [
                'id' => $u->id,
                'label' => trim(($u->full_name ?: $u->name) . ($u->id === $authUser->id ? ' (Saya)' : '')),
            ])
            ->values();

        // =========================
        // TEAM PERFORMANCE (units selesai) -> scope: downline baseUser
        // =========================
        $teamPerformance = User::query()
            ->whereIn('users.id', $childIds) // ranking untuk downline-nya saja
            ->leftJoin('sales_orders', function ($join) use ($from, $to) {
                $join->on('sales_orders.sales_user_id', '=', 'users.id')
                    ->whereNull('sales_orders.deleted_at')
                    ->where('sales_orders.status', 'selesai');

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
        // MY TOTAL UNITS -> sekarang mengikuti baseUser (biar konsisten saat pilih member)
        // =========================
        $myTotalUnitsQ = DB::table('sales_orders')
            ->whereNull('sales_orders.deleted_at')
            ->where('sales_orders.sales_user_id', $baseUser->id)
            ->where('sales_orders.status', 'selesai')
            ->leftJoin('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id');

        if ($from) $myTotalUnitsQ->whereDate('sales_orders.install_date', '>=', $from);
        if ($to)   $myTotalUnitsQ->whereDate('sales_orders.install_date', '<=', $to);

        $myTotalUnits = (int) $myTotalUnitsQ
            ->selectRaw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            ->value('units');

        // =========================
        // SUMMARY CARDS -> scope: baseUser + downline baseUser
        // =========================
        $summaryQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        if ($from) $summaryQ->whereDate('so.key_in_at', '>=', $from);
        if ($to)   $summaryQ->whereDate('so.key_in_at', '<=', $to);
        if ($from || $to) $summaryQ->whereNotNull('so.key_in_at');

        $summary = $summaryQ->selectRaw("
        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 0
                 AND so.ccp_status = 'menunggu pengecekan'
                 AND (so.status = 'menunggu verifikasi' OR so.status = 'dibatalkan')
                THEN 1 ELSE 0
            END
        ) as total_key_in,

        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'menunggu pengecekan'
                 AND so.status = 'menunggu verifikasi'
                THEN 1 ELSE 0
            END
        ) as total_recurring,

        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'disetujui'
                 AND so.status = 'menunggu jadwal'
                THEN 1 ELSE 0
            END
        ) as menunggu_jadwal,

        SUM(
            CASE
                WHEN COALESCE(so.is_recurring, 0) = 1
                 AND so.ccp_status = 'disetujui'
                 AND so.status IN ('dijadwalkan', 'ditunda', 'gagal penelponan')
                THEN 1 ELSE 0
            END
        ) as task_id,

        SUM(
            CASE
                WHEN so.status = 'selesai'
                THEN 1 ELSE 0
            END
        ) as total_sudah_install
    ")->first();

        // =========================
        // TEAM SHEET -> scope: baseUser + downline baseUser (bukan cuma downline)
        // =========================
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

        if ($from) $sheetQ->whereDate('so.key_in_at', '>=', $from);
        if ($to)   $sheetQ->whereDate('so.key_in_at', '<=', $to);
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
                'so.status_reason',
                'so.ccp_remarks',
                DB::raw("COALESCE(NULLIF(so.status_reason,''), NULLIF(so.ccp_remarks,''), '-') as remarks"),
                DB::raw("CASE WHEN so.ccp_status = 'disetujui' THEN so.updated_at ELSE NULL END as ccp_approved_at"),
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
            ->get()
            ->groupBy('hp_name');

        return view('performances.index', [
            'teamPerformance' => $teamPerformance,
            'teamMemberCount' => $childIds->count(),     // direct reports dari baseUser
            'myTotalUnits'    => $myTotalUnits,          // total units baseUser
            'q'               => $q,                     // tidak dipakai untuk query, tapi boleh tetap dipassing
            'from'            => $from,
            'to'              => $to,
            'summary'         => $summary,
            'teamSheetRows'   => $teamSheetRows,

            // ✅ tambahan untuk dropdown
            'memberOptions'   => $memberOptions,
            'memberId'        => $baseUser->id,          // selected (default auth)
            'memberLabel'     => ($baseUser->full_name ?: $baseUser->name),
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
