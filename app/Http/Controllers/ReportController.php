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

        // period: weekly | monthly
        $period = $request->get('period', 'weekly');
        if (!in_array($period, ['weekly', 'monthly'], true)) {
            $period = 'weekly';
        }

        [$start, $end, $label] = $this->dateRange($period);

        // =========================
        // 1) Tentukan kandidat yang di-rank (target rows)
        // =========================
        $isAdminLike = $user->hasAnyRole(['Admin', 'Head Admin', 'Sales Manager']);
        $isHM        = $user->hasRole('Health Manager');

        if ($isAdminLike) {
            // Semua Health Manager di sistem
            $targetsQ = User::query()
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Manager'));
        } elseif ($isHM) {
            // Semua Health Planner direct bawahan HM
            $targetsQ = User::query()
                ->whereIn('users.id', $user->childrenUsers()->pluck('users.id'))
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'));
        } else {
            // Health Planner: semua direct bawahan
            $targetsQ = User::query()
                ->whereIn('users.id', $user->childrenUsers()->pluck('users.id'));
        }

        $targets = $targetsQ->select('users.id', 'users.name')->get();
        $targetIds = $targets->pluck('id')->map(fn($v) => (int)$v)->values();

        if ($targetIds->isEmpty()) {
            return view('reports.index', [
                'period'        => $period,
                'rangeLabel'    => $label,
                'start'         => $start,
                'end'           => $end,
                'topPerformers' => collect(),
                'leaderboard'   => collect(),
                'chartLabels'   => collect(),
                'chartUnits'    => collect(),
            ]);
        }

        // =========================
        // 2) Units per seller (SUM qty) hanya status selesai
        // =========================
        $unitsPerSeller = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.status', 'selesai')
            ->whereBetween('sales_orders.created_at', [$start, $end])
            ->groupBy('sales_orders.sales_user_id')
            ->select(
                'sales_orders.sales_user_id',
                DB::raw('COALESCE(SUM(sales_order_items.qty), 0) as units')
            );

        // =========================
        // 3) Recursive CTE: descendants (target -> semua turunan) + include self
        //    user_hierarchies: parent_user_id, child_user_id
        // =========================
        $targetsInline = '(' . $targetIds->implode(',') . ')';

        // NOTE: kalau kamu ingin filter relation_type tertentu, tambahkan:
        // AND uh.relation_type = '...'
        $cteSql = "
            WITH RECURSIVE descendants AS (
                -- anchor: tiap target adalah descendant dirinya sendiri
                SELECT u.id AS ancestor_id, u.id AS descendant_id
                FROM users u
                WHERE u.id IN {$targetsInline}

                UNION ALL

                -- recursive: ambil anak dari descendant sebelumnya
                SELECT d.ancestor_id, uh.child_user_id AS descendant_id
                FROM descendants d
                JOIN user_hierarchies uh
                    ON uh.parent_user_id = d.descendant_id
            )
            SELECT d.ancestor_id, COALESCE(SUM(u.units), 0) AS units
            FROM descendants d
            LEFT JOIN (
                " . $unitsPerSeller->toSql() . "
            ) u
                ON u.sales_user_id = d.descendant_id
            GROUP BY d.ancestor_id
        ";

        // binding untuk subquery $unitsPerSeller
        $unitsPerTargetRows = DB::select($cteSql, $unitsPerSeller->getBindings());

        // map: ancestor_id => units
        $unitsMap = collect($unitsPerTargetRows)
            ->mapWithKeys(fn($r) => [(int)$r->ancestor_id => (int)$r->units]);

        // =========================
        // 4) Build leaderboard TOP 10 (sesuai requirement)
        // =========================
        $leaderboard = $targets
            ->map(function ($t) use ($unitsMap) {
                $id = (int)$t->id;
                return [
                    'id'    => $id,
                    'name'  => (string)$t->name,
                    'units' => (int)($unitsMap[$id] ?? 0),
                ];
            })
            ->sortBy([
                ['units', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->take(10)
            ->values()
            ->map(function ($row, $idx) {
                return [
                    'rank'  => $idx + 1,
                    'id'    => $row['id'],
                    'name'  => $row['name'],
                    'units' => $row['units'],
                ];
            });

        $topPerformers = $leaderboard;

        return view('reports.index', [
            'period'        => $period,
            'rangeLabel'    => $label,
            'start'         => $start,
            'end'           => $end,
            'topPerformers' => $topPerformers,
            'leaderboard'   => $leaderboard,
            'chartLabels'   => $topPerformers->pluck('name'),
            'chartUnits'    => $topPerformers->pluck('units'),
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

        $start = $now->copy()->subDays(6)->startOfDay();
        $end   = $now->copy()->endOfDay();
        $label = 'Last 7 Days';
        return [$start, $end, $label];
    }
}
