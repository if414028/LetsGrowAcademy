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

        $teamPerformance = User::query()
            ->whereIn('users.id', $childIds)
            ->leftJoin('sales_orders', 'sales_orders.sales_user_id', '=', 'users.id')
            ->leftJoin('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id') // ✅ JOIN ITEMS
            ->select(
                'users.id',
                'users.name',
                DB::raw('COALESCE(SUM(sales_order_items.qty), 0) as units') // ✅ SUM UNIT
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('units')
            ->get()
            ->map(function ($u) {
                $lastOrder = SalesOrder::where('sales_user_id', $u->id)
                    ->latest('created_at')
                    ->first();

                return [
                    'id'            => $u->id,
                    'name'          => $u->name,
                    'units'         => (int) $u->units,
                    'last_activity' => $lastOrder?->order_no ?? 'N/A',
                ];
            });

        // ✅ My total units juga harus SUM item qty (bukan count order)
        $myTotalUnits = DB::table('sales_orders')
            ->where('sales_orders.sales_user_id', $user->id)
            ->leftJoin('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->selectRaw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            ->value('units');

        return view('performances.index', [
            'teamPerformance' => $teamPerformance,
            'teamMemberCount' => $childIds->count(),
            'myTotalUnits'    => (int) $myTotalUnits,
            'q'               => $request->q,
        ]);
    }

    public function teamDetail(Request $request, User $user)
    {
        $auth = request()->user();

        // ✅ Security: pastikan yg dibuka adalah bawahan langsung
        $isChild = $auth->childrenUsers()->where('users.id', $user->id)->exists();
        abort_unless($isChild, 403);

        // NOTE: ganti qty kalau kolom di sales_order_items beda
        $totalUnits = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->sum('soi.qty');

        $orders = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->orderByDesc('so.key_in_at')
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
