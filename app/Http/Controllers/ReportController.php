<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCutoff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ======================================
        // Date range: manual From-To
        // Default = Closing Date (cutoff aktif)
        // ======================================
        [$from, $to, $isManual] = $this->normalizeDateRange(
            $request->get('from'),
            $request->get('to')
        );

        $cutoff = PerformanceCutoff::current();

        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date ?? Carbon::now()->endOfMonth()->toDateString();

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $rangeLabel = $isManual ? 'Custom Range' : 'Closing Date';

        $isAdminLike = $user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']);
        $isHM = $user->hasRole('Health Manager');

        // =========================================================
        // LEADERBOARD HEALTH MANAGER
        // hanya untuk Sales Manager / Admin / Head Admin
        // =========================================================
        $hmLeaderboard = collect();

        if ($isAdminLike) {
            $hmTargets = User::query()
                ->where('status', 'Active')
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Manager'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();

            $hmLeaderboard = $this->buildLeaderboardWithDescendants($hmTargets, $from, $to);
        }

        // =========================================================
        // LEADERBOARD HEALTH PLANNER
        // - Admin-like: semua Health Planner
        // - HM / HP: semua bawahan user login
        // =========================================================
        if ($isAdminLike) {
            $hpTargets = User::query()
                ->where('status', 'Active')
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();

            // HP leaderboard = per HP sendiri, tanpa descendants
            $hpLeaderboard = $this->buildSelfLeaderboard($hpTargets, $from, $to);
        } else {
            $hpTargets = User::query()
                ->whereIn('users.id', $user->childrenUsers()->pluck('users.id'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();

            // untuk HM / HP, tampilkan bawahan langsung saja
            $hpLeaderboard = $this->buildSelfLeaderboard($hpTargets, $from, $to);
        }

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'rangeLabel' => $rangeLabel,

            'showHmLeaderboard' => $isAdminLike,

            'hmLeaderboard' => $hmLeaderboard,
            'hmChartLabels' => $hmLeaderboard->pluck('name'),
            'hmChartUnits' => $hmLeaderboard->pluck('units'),

            'hpLeaderboard' => $hpLeaderboard,
            'hpChartLabels' => $hpLeaderboard->pluck('name'),
            'hpChartUnits' => $hpLeaderboard->pluck('units'),
        ]);
    }

    /**
     * Leaderboard untuk target + semua descendants.
     * Cocok untuk Health Manager.
     */
    private function buildLeaderboardWithDescendants($targets, string $from, string $to)
    {
        $targetIds = $targets->pluck('id')->map(fn($v) => (int) $v)->values();

        if ($targetIds->isEmpty()) {
            return collect();
        }

        // Units per seller = total qty, bukan total SO
        // bundle di-expand
        $unitsExpr = "
            COALESCE(SUM(
                CASE
                    WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                    ELSE soi.qty
                END
            ), 0)
        ";

        $unitsPerSeller = DB::table('sales_orders as so')
            ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->whereNull('so.deleted_at')
            ->where('so.status', 'selesai')
            ->whereNotNull('so.install_date')
            ->whereDate('so.install_date', '>=', $from)
            ->whereDate('so.install_date', '<=', $to)
            ->groupBy('so.sales_user_id')
            ->select(
                'so.sales_user_id',
                DB::raw("{$unitsExpr} as units")
            );

        $targetsInline = '(' . $targetIds->implode(',') . ')';

        $cteSql = "
            WITH RECURSIVE descendants AS (
                SELECT u.id AS ancestor_id, u.id AS descendant_id
                FROM users u
                WHERE u.id IN {$targetsInline}

                UNION ALL

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

        $rows = DB::select($cteSql, $unitsPerSeller->getBindings());

        $unitsMap = collect($rows)
            ->mapWithKeys(fn($r) => [(int) $r->ancestor_id => (int) $r->units]);

        return $targets
            ->map(function ($t) use ($unitsMap) {
                $id = (int) $t->id;

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'units' => (int) ($unitsMap[$id] ?? 0),
                ];
            })
            ->sortBy([
                ['units', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->take(10)
            ->values()
            ->map(fn($row, $idx) => [
                'rank' => $idx + 1,
                'id' => $row['id'],
                'name' => $row['name'],
                'units' => $row['units'],
            ]);
    }

    /**
     * Leaderboard per user sendiri.
     * Cocok untuk Health Planner.
     */
    private function buildSelfLeaderboard($targets, string $from, string $to)
    {
        $targetIds = $targets->pluck('id')->map(fn($v) => (int) $v)->values();

        if ($targetIds->isEmpty()) {
            return collect();
        }

        $unitsExpr = "
            COALESCE(SUM(
                CASE
                    WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                    ELSE soi.qty
                END
            ), 0)
        ";

        $rows = DB::table('sales_orders as so')
            ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->whereNull('so.deleted_at')
            ->where('so.status', 'selesai')
            ->whereNotNull('so.install_date')
            ->whereDate('so.install_date', '>=', $from)
            ->whereDate('so.install_date', '<=', $to)
            ->whereIn('so.sales_user_id', $targetIds)
            ->groupBy('so.sales_user_id')
            ->select(
                'so.sales_user_id',
                DB::raw("{$unitsExpr} as units")
            )
            ->get();

        $unitsMap = $rows->mapWithKeys(fn($r) => [(int) $r->sales_user_id => (int) $r->units]);

        return $targets
            ->map(function ($t) use ($unitsMap) {
                $id = (int) $t->id;

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'units' => (int) ($unitsMap[$id] ?? 0),
                ];
            })
            ->sortBy([
                ['units', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->take(10)
            ->values()
            ->map(fn($row, $idx) => [
                'rank' => $idx + 1,
                'id' => $row['id'],
                'name' => $row['name'],
                'units' => $row['units'],
            ]);
    }

    private function normalizeDateRange(?string $from, ?string $to): array
    {
        $from = trim((string) $from);
        $to   = trim((string) $to);

        $from = $from !== '' ? $from : null;
        $to   = $to !== '' ? $to : null;

        $isManual = (bool) ($from || $to);

        if ($isManual) {
            if ($from && !$to) {
                $to = Carbon::parse($from)->endOfMonth()->toDateString();
            }

            if (!$from && $to) {
                $from = Carbon::parse($to)->startOfMonth()->toDateString();
            }
        }

        return [$from, $to, $isManual];
    }
}
