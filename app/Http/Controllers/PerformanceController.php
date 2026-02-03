<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $childIds = $user->childrenUsers()->pluck('users.id');

        $q = trim((string) $request->get('q', ''));

        // date range (install_date)
        $from = $request->get('from'); // format: YYYY-MM-DD
        $to   = $request->get('to');

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

                $lastOrder = $lastOrderQ->latest('install_date')->first(); // atau key_in_at

                return [
                    'id'            => $u->id,
                    'name'          => $u->name,
                    'units'         => (int) $u->units,
                    'last_activity' => $lastOrder?->order_no ?? 'N/A',
                ];
            });

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

        return view('performances.index', [
            'teamPerformance' => $teamPerformance,
            'teamMemberCount' => $childIds->count(),
            'myTotalUnits'    => (int) $myTotalUnits,
            'q'               => $q,
            'from'            => $from,
            'to'              => $to,
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
