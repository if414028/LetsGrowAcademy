<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCutoff;
use App\Models\User;
use App\Models\UserHierarchy;
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

        // =========================================================
        // LEADERBOARD HEALTH MANAGER
        // hanya untuk Sales Manager / Admin / Head Admin
        // HM = HM + semua bawahannya
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
        // - Admin-like: semua Health Planner aktif
        // - selain admin-like: seluruh downline Health Planner aktif user login
        // HP = pribadi saja, TANPA bawahan
        // =========================================================
        if ($isAdminLike) {
            $hpTargets = User::query()
                ->where('status', 'Active')
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();
        } else {
            $downlineIds = $user->downlineUserIds();

            $hpTargets = User::query()
                ->where('status', 'Active')
                ->whereIn('users.id', $downlineIds)
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();
        }

        $hpHealthManagerNames = $this->nearestHealthManagerNames($hpTargets->pluck('id'));
        $hpLeaderboard = $this->buildPersonalLeaderboard($hpTargets, $from, $to, $hpHealthManagerNames);

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
     * Dipakai untuk Health Manager.
     */
    private function buildLeaderboardWithDescendants($targets, string $from, string $to)
    {
        if ($targets->isEmpty()) {
            return collect();
        }

        $scopeByTarget = $targets
            ->mapWithKeys(function ($target) {
                $scopeIds = $target->downlineUserIds()
                    ->push((int) $target->id)
                    ->unique()
                    ->values();

                return [(int) $target->id => $scopeIds];
            });

        $allScopeIds = $scopeByTarget
            ->flatMap(fn($scopeIds) => $scopeIds)
            ->unique()
            ->values();

        // Samakan definisi Active HP dengan Dashboard: user HP berstatus Active
        // yang memiliki minimal satu SO selesai berdasarkan key-in pada periode terpilih.
        $activeHealthPlannerIds = User::query()
            ->role('Health Planner')
            ->where('users.status', 'Active')
            ->whereIn('users.id', $allScopeIds->all())
            ->whereExists(function ($query) use ($from, $to) {
                $query->select(DB::raw(1))
                    ->from('sales_orders')
                    ->whereColumn('sales_orders.sales_user_id', 'users.id')
                    ->whereNull('sales_orders.deleted_at')
                    ->where('sales_orders.status', 'selesai')
                    ->whereBetween('sales_orders.key_in_at', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay(),
                    ]);
            })
            ->pluck('users.id')
            ->map(fn($id) => (int) $id)
            ->flip();

        $sellerStats = DB::table('sales_orders as so')
            ->leftJoinSub($this->salesOrderUnitSubquery(), 'sou', function ($join) {
                $join->on('sou.sales_order_id', '=', 'so.id');
            })
            ->whereNull('so.deleted_at')
            ->where('so.status', 'selesai')
            ->whereNotNull('so.install_date')
            ->whereDate('so.install_date', '>=', $from)
            ->whereDate('so.install_date', '<=', $to)
            ->whereIn('so.sales_user_id', $allScopeIds->all())
            ->groupBy('so.sales_user_id')
            ->select(
                'so.sales_user_id',
                DB::raw('COALESCE(SUM(sou.unit_count), 0) as units'),
                DB::raw('MIN(so.key_in_at) as first_key_in_at')
            )
            ->get()
            ->keyBy(fn($row) => (int) $row->sales_user_id);

        return $targets
            ->map(function ($t) use ($scopeByTarget, $sellerStats, $activeHealthPlannerIds) {
                $id = (int) $t->id;
                $scopeIds = $scopeByTarget->get($id, collect([$id]));
                $units = $scopeIds->sum(fn($sellerId) => (int) optional($sellerStats->get((int) $sellerId))->units);
                $activeHealthPlanners = $scopeIds
                    ->filter(fn($userId) => $activeHealthPlannerIds->has((int) $userId))
                    ->count();
                $firstKeyIn = $scopeIds
                    ->map(fn($sellerId) => optional($sellerStats->get((int) $sellerId))->first_key_in_at)
                    ->filter()
                    ->sort()
                    ->first();

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'units' => (int) $units,
                    'active_hp' => $activeHealthPlanners,
                    'first_key_in_at' => $firstKeyIn,
                    'first_key_in_sort' => $firstKeyIn ?? '9999-12-31 23:59:59',
                ];
            })
            ->filter(fn($row) => $row['units'] > 0)
            ->sortBy([
                ['units', 'desc'],
                ['first_key_in_sort', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->map(fn($row, $idx) => [
                'rank' => $idx + 1,
                'id' => $row['id'],
                'name' => $row['name'],
                'units' => $row['units'],
                'active_hp' => $row['active_hp'],
            ]);
    }

    /**
     * Leaderboard personal saja (tanpa bawahan).
     * Dipakai untuk Health Planner.
     */
    private function buildPersonalLeaderboard($targets, string $from, string $to, $healthManagerNames = null)
    {
        $healthManagerNames ??= collect();
        $targetIds = $targets->pluck('id')->map(fn($v) => (int) $v)->values();

        if ($targetIds->isEmpty()) {
            return collect();
        }

        $activeHpScopeByTarget = $targets
            ->mapWithKeys(function ($target) {
                $scopeIds = $target->downlineUserIds()
                    ->push((int) $target->id)
                    ->unique()
                    ->values();

                return [(int) $target->id => $scopeIds];
            });

        $allActiveHpScopeIds = $activeHpScopeByTarget
            ->flatMap(fn($scopeIds) => $scopeIds)
            ->unique()
            ->values();

        $activeHealthPlannerIds = User::query()
            ->role('Health Planner')
            ->where('users.status', 'Active')
            ->whereIn('users.id', $allActiveHpScopeIds->all())
            ->whereExists(function ($query) use ($from, $to) {
                $query->select(DB::raw(1))
                    ->from('sales_orders')
                    ->whereColumn('sales_orders.sales_user_id', 'users.id')
                    ->whereNull('sales_orders.deleted_at')
                    ->where('sales_orders.status', 'selesai')
                    ->whereBetween('sales_orders.key_in_at', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay(),
                    ]);
            })
            ->pluck('users.id')
            ->map(fn($id) => (int) $id)
            ->flip();

        $rows = DB::table('sales_orders as so')
            ->leftJoinSub($this->salesOrderUnitSubquery(), 'sou', function ($join) {
                $join->on('sou.sales_order_id', '=', 'so.id');
            })
            ->whereNull('so.deleted_at')
            ->where('so.status', 'selesai')
            ->whereNotNull('so.install_date')
            ->whereDate('so.install_date', '>=', $from)
            ->whereDate('so.install_date', '<=', $to)
            ->whereIn('so.sales_user_id', $targetIds->all())
            ->groupBy('so.sales_user_id')
            ->select(
                'so.sales_user_id',
                DB::raw('COALESCE(SUM(sou.unit_count), 0) as units'),
                DB::raw('MIN(so.key_in_at) as first_key_in_at')
            )
            ->get();

        $leaderboardMap = collect($rows)
            ->mapWithKeys(fn($r) => [(int) $r->sales_user_id => [
                'units' => (int) $r->units,
                'first_key_in_at' => $r->first_key_in_at,
            ]]);

        return $targets
            ->map(function ($t) use ($leaderboardMap, $healthManagerNames, $activeHealthPlannerIds, $activeHpScopeByTarget) {
                $id = (int) $t->id;
                $leaderboard = $leaderboardMap[$id] ?? ['units' => 0, 'first_key_in_at' => null];
                $activeHealthPlanners = $activeHpScopeByTarget
                    ->get($id, collect([$id]))
                    ->filter(fn($userId) => $activeHealthPlannerIds->has((int) $userId))
                    ->count();

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'health_manager_name' => $healthManagerNames->get($id),
                    'units' => (int) $leaderboard['units'],
                    'active_hp' => $activeHealthPlanners,
                    'first_key_in_at' => $leaderboard['first_key_in_at'],
                    'first_key_in_sort' => $leaderboard['first_key_in_at'] ?? '9999-12-31 23:59:59',
                ];
            })
            ->filter(fn($row) => $row['units'] > 0)
            ->sortBy([
                ['units', 'desc'],
                ['first_key_in_sort', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->map(fn($row, $idx) => [
                'rank' => $idx + 1,
                'id' => $row['id'],
                'name' => $row['name'],
                'health_manager_name' => $row['health_manager_name'],
                'units' => $row['units'],
                'active_hp' => $row['active_hp'],
            ]);
    }

    /**
     * Nama Health Manager terdekat pada jalur upline setiap Health Planner.
     */
    private function nearestHealthManagerNames($healthPlannerIds)
    {
        $currentByHealthPlanner = $healthPlannerIds
            ->mapWithKeys(fn($id) => [(int) $id => (int) $id]);
        $visitedByHealthPlanner = $currentByHealthPlanner
            ->map(fn($id) => [$id => true])
            ->all();
        $names = collect();

        while ($currentByHealthPlanner->isNotEmpty()) {
            $parentByChild = UserHierarchy::query()
                ->whereIn('child_user_id', $currentByHealthPlanner->values()->unique()->all())
                ->pluck('parent_user_id', 'child_user_id')
                ->mapWithKeys(fn($parentId, $childId) => [(int) $childId => (int) $parentId]);

            if ($parentByChild->isEmpty()) {
                break;
            }

            $healthManagers = User::query()
                ->whereIn('users.id', $parentByChild->values()->unique()->all())
                ->whereHas('roles', fn($query) => $query->where('name', 'Health Manager'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get()
                ->mapWithKeys(fn($user) => [
                    (int) $user->id => trim((string) ($user->full_name ?: $user->name)),
                ]);

            $next = collect();

            foreach ($currentByHealthPlanner as $healthPlannerId => $currentId) {
                $parentId = $parentByChild->get($currentId);

                if (!$parentId) {
                    continue;
                }

                if ($healthManagers->has($parentId)) {
                    $names->put((int) $healthPlannerId, $healthManagers->get($parentId));
                    continue;
                }

                if (isset($visitedByHealthPlanner[$healthPlannerId][$parentId])) {
                    continue;
                }

                $visitedByHealthPlanner[$healthPlannerId][$parentId] = true;
                $next->put((int) $healthPlannerId, (int) $parentId);
            }

            $currentByHealthPlanner = $next;
        }

        return $names;
    }

    private function activeBundleChildUnitSubquery()
    {
        return DB::table('sales_order_items as child_soi')
            ->whereNotNull('child_soi.parent_item_id')
            ->whereRaw('COALESCE(child_soi.is_cancelled, 0) = 0')
            ->selectRaw('child_soi.parent_item_id, COALESCE(SUM(child_soi.qty), 0) as active_child_qty')
            ->groupBy('child_soi.parent_item_id');
    }

    private function bundleDefinitionUnitSubquery()
    {
        return DB::table('bundle_items as bundle_unit')
            ->selectRaw('bundle_unit.bundle_id, COALESCE(SUM(bundle_unit.qty), 0) as bundle_unit_qty')
            ->groupBy('bundle_unit.bundle_id');
    }

    private function salesOrderUnitSubquery()
    {
        return DB::table('sales_order_items as parent_soi')
            ->whereNull('parent_soi.parent_item_id')
            ->whereRaw('COALESCE(parent_soi.is_cancelled, 0) = 0')
            ->leftJoin('products as unit_product', 'unit_product.id', '=', 'parent_soi.product_id')
            ->leftJoinSub($this->activeBundleChildUnitSubquery(), 'active_child_units', function ($join) {
                $join->on('active_child_units.parent_item_id', '=', 'parent_soi.id');
            })
            ->leftJoinSub($this->bundleDefinitionUnitSubquery(), 'bundle_definition_units', function ($join) {
                $join->on('bundle_definition_units.bundle_id', '=', 'parent_soi.product_id');
            })
            ->selectRaw("
                parent_soi.sales_order_id,
                COALESCE(SUM(
                    CASE
                        WHEN unit_product.type = 'bundle'
                        THEN COALESCE(
                            active_child_units.active_child_qty,
                            parent_soi.qty * COALESCE(bundle_definition_units.bundle_unit_qty, 1)
                        )
                        ELSE parent_soi.qty
                    END
                ), 0) as unit_count
            ")
            ->groupBy('parent_soi.sales_order_id');
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
