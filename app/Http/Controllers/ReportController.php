<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ambil semua bawahan (children)
        $childIds = $user->childrenUsers()->pluck('users.id');

        // period: weekly | monthly
        $period = $request->get('period', 'weekly');
        if (!in_array($period, ['weekly', 'monthly'], true)) {
            $period = 'weekly';
        }

        [$start, $end, $label] = $this->dateRange($period);

        // ===== Units aggregate (orders join items) =====
        $itemsAgg = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select(
                'sales_orders.sales_user_id',
                DB::raw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            )
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->groupBy('sales_orders.sales_user_id');

        // ===== Leaderboard: semua bawahan (units, revenue=0 sementara) =====
        $leaderboard = User::query()
            ->whereIn('users.id', $childIds)
            ->leftJoinSub($itemsAgg, 'items_agg', function ($join) {
                $join->on('items_agg.sales_user_id', '=', 'users.id');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COALESCE(items_agg.units, 0) as units'),
                DB::raw('0 as revenue') // ✅ revenue belum tersedia di schema
            )
            ->orderByDesc('units')
            ->orderBy('users.name')
            ->get()
            ->values()
            ->map(function ($row, $idx) {
                return [
                    'rank'    => $idx + 1,
                    'id'      => (int) $row->id,
                    'name'    => (string) $row->name,
                    'units'   => (int) $row->units,
                    'revenue' => 0.0,
                ];
            });

        // Top performers: top 10 dari leaderboard
        $topPerformers = $leaderboard->take(10)->values();

        // data chart
        $chartLabels = $topPerformers->pluck('name');
        $chartUnits  = $topPerformers->pluck('units');

        return view('reports.index', [
            'period'        => $period,
            'rangeLabel'    => $label,
            'start'         => $start,
            'end'           => $end,
            'topPerformers' => $topPerformers,
            'leaderboard'   => $leaderboard,
            'chartLabels'   => $chartLabels,
            'chartUnits'    => $chartUnits,
        ]);
    }

    private function dateRange(string $period): array
    {
        $now = Carbon::now();

        if ($period === 'monthly') {
            $start = $now->copy()->startOfMonth();
            $end   = $now->copy()->endOfDay();
            $label = 'This Month';
            return [$start, $end, $label];
        }

        // weekly default: last 7 days
        $start = $now->copy()->subDays(6)->startOfDay();
        $end   = $now->copy()->endOfDay();
        $label = 'Last 7 Days';
        return [$start, $end, $label];
    }
}
