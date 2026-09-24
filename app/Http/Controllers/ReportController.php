<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCutoff;
use App\Models\User;
use App\Models\UserHierarchy;
use App\Services\HealthManagerNsScope;
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
        $hpLeaderboardScope = in_array($request->get('hp_scope'), ['personal', 'team'], true)
            ? $request->get('hp_scope')
            : 'personal';

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
        // HP = mode personal (akun + secondary) atau team (+ seluruh downliner)
        // =========================================================
        if ($isAdminLike) {
            $hpTargets = User::query()
                ->where('status', 'Active')
                ->where('is_secondary_account', false)
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();
        } else {
            $downlineIds = $user->downlineUserIds();

            $hpTargets = User::query()
                ->where('status', 'Active')
                ->whereIn('users.id', $downlineIds)
                ->where('is_secondary_account', false)
                ->whereHas('roles', fn($q) => $q->where('name', 'Health Planner'))
                ->select('users.id', 'users.name', 'users.full_name')
                ->get();
        }

        $hpHealthManagerNames = $this->nearestHealthManagerNames($hpTargets->pluck('id'));
        $hpLeaderboard = $this->buildHealthPlannerLeaderboard(
            $hpTargets,
            $from,
            $to,
            $hpHealthManagerNames,
            $hpLeaderboardScope
        );

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'rangeLabel' => $rangeLabel,
            'isManualRange' => $isManual,
            'hpLeaderboardScope' => $hpLeaderboardScope,

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

        return $targets
            ->map(function ($t) use ($from, $to) {
                $id = (int) $t->id;
                $query = DB::table('sales_orders as so')
                    ->leftJoinSub($this->salesOrderUnitSubquery(), 'sou', function ($join) {
                        $join->on('sou.sales_order_id', '=', 'so.id');
                    })
                    ->whereNull('so.deleted_at')
                    ->where('so.status', 'selesai')
                    ->whereNotNull('so.install_date')
                    ->whereDate('so.install_date', '>=', $from)
                    ->whereDate('so.install_date', '<=', $to);

                HealthManagerNsScope::apply($query, $t, 'so', 'so.install_date');
                $stats = $query->selectRaw('COALESCE(SUM(sou.unit_count), 0) as units, MIN(so.key_in_at) as first_key_in_at, MIN(so.install_date) as first_install_date')->first();

                $activeSellerQuery = DB::table('sales_orders as active_so')
                    ->whereNull('active_so.deleted_at')
                    ->where('active_so.status', 'selesai')
                    ->whereNotNull('active_so.install_date')
                    ->whereDate('active_so.install_date', '>=', $from)
                    ->whereDate('active_so.install_date', '<=', $to);
                HealthManagerNsScope::apply($activeSellerQuery, $t, 'active_so', 'active_so.install_date');
                $activeSellerIds = $activeSellerQuery
                    ->distinct()
                    ->pluck('active_so.sales_user_id')
                    ->map(fn($sellerId) => (int) $sellerId);

                $activeHealthPlanners = User::query()
                    ->role('Health Planner')
                    ->where('users.status', 'Active')
                    ->whereIn('users.id', $activeSellerIds)
                    ->count();

                $units = (int) ($stats->units ?? 0);
                $firstKeyIn = $stats->first_key_in_at ?? null;
                $firstInstallDate = $stats->first_install_date ?? null;

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'units' => (int) $units,
                    'active_hp' => $activeHealthPlanners,
                    'first_key_in_at' => $firstKeyIn,
                    'first_key_in_sort' => $firstKeyIn ?? '9999-12-31 23:59:59',
                    'first_install_date' => $firstInstallDate,
                    'first_install_date_sort' => $firstInstallDate ?? '9999-12-31',
                ];
            })
            ->filter(fn($row) => $row['units'] > 0)
            ->sortBy([
                ['units', 'desc'],
                ['first_key_in_sort', 'asc'],
                ['first_install_date_sort', 'asc'],
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
     * Leaderboard HP berdasarkan keluarga akun.
     * Personal: akun utama + secondary account.
     * Team: akun utama + secondary account + seluruh downliner beserta secondary account mereka.
     */
    private function buildHealthPlannerLeaderboard(
        $targets,
        string $from,
        string $to,
        $healthManagerNames = null,
        string $scope = 'personal'
    )
    {
        $healthManagerNames ??= collect();
        $targetIds = $targets->pluck('id')->map(fn($v) => (int) $v)->values();

        if ($targetIds->isEmpty()) {
            return collect();
        }

        $primaryAccountIdsByTarget = $targets
            ->mapWithKeys(function ($target) use ($scope) {
                $candidateIds = $scope === 'team'
                    ? $target->downlineUserIds()->push((int) $target->id)
                    : collect([(int) $target->id]);

                $primaryAccountIds = User::query()
                    ->whereIn('id', $candidateIds->unique()->all())
                    ->where('is_secondary_account', false)
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values();

                return [(int) $target->id => $primaryAccountIds];
            });

        $allPrimaryAccountIds = $primaryAccountIdsByTarget
            ->flatMap(fn($primaryIds) => $primaryIds)
            ->unique()
            ->values();

        // A primary account can have more than one secondary account.
        $secondaryAccountIdsByPrimary = User::query()
            ->where('is_secondary_account', true)
            ->whereIn('primary_account_id', $allPrimaryAccountIds->all())
            ->get(['id', 'primary_account_id'])
            ->groupBy(fn($secondary) => (int) $secondary->primary_account_id)
            ->map(fn($secondaries) => $secondaries->pluck('id')->map(fn($id) => (int) $id)->values());

        $salesScopeByTarget = $primaryAccountIdsByTarget
            ->map(function ($primaryIds) use ($secondaryAccountIdsByPrimary) {
                return $primaryIds
                    ->flatMap(fn($primaryId) => collect([(int) $primaryId])
                        ->merge($secondaryAccountIdsByPrimary->get((int) $primaryId, collect())))
                    ->unique()
                    ->values();
            });

        $allSalesUserIds = $salesScopeByTarget
            ->flatMap(fn($salesUserIds) => $salesUserIds)
            ->unique()
            ->values();

        $activeHealthPlannerIds = User::query()
            ->role('Health Planner')
            ->where('users.status', 'Active')
            ->where('users.is_secondary_account', false)
            ->whereIn('users.id', $allPrimaryAccountIds->all())
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
            ->whereIn('so.sales_user_id', $allSalesUserIds->all())
            ->groupBy('so.sales_user_id')
            ->select(
                'so.sales_user_id',
                DB::raw('COALESCE(SUM(sou.unit_count), 0) as units'),
                DB::raw('MIN(so.key_in_at) as first_key_in_at'),
                DB::raw('MIN(so.install_date) as first_install_date')
            )
            ->get();

        $leaderboardMap = collect($rows)
            ->mapWithKeys(fn($r) => [(int) $r->sales_user_id => [
                'units' => (int) $r->units,
                'first_key_in_at' => $r->first_key_in_at,
                'first_install_date' => $r->first_install_date,
            ]]);

        return $targets
            ->map(function ($t) use (
                $leaderboardMap,
                $healthManagerNames,
                $activeHealthPlannerIds,
                $primaryAccountIdsByTarget,
                $secondaryAccountIdsByPrimary,
                $salesScopeByTarget
            ) {
                $id = (int) $t->id;
                $salesScope = $salesScopeByTarget->get($id, collect([$id]));
                $stats = $salesScope
                    ->map(fn($salesUserId) => $leaderboardMap->get((int) $salesUserId))
                    ->filter();
                $units = $stats->sum('units');
                $firstKeyInAt = $stats->pluck('first_key_in_at')->filter()->sort()->first();
                $firstInstallDate = $stats->pluck('first_install_date')->filter()->sort()->first();

                $activeHealthPlanners = $primaryAccountIdsByTarget
                    ->get($id, collect([$id]))
                    ->filter(function ($primaryId) use ($activeHealthPlannerIds, $secondaryAccountIdsByPrimary, $leaderboardMap) {
                        if (!$activeHealthPlannerIds->has((int) $primaryId)) {
                            return false;
                        }

                        return collect([(int) $primaryId])
                            ->merge($secondaryAccountIdsByPrimary->get((int) $primaryId, collect()))
                            ->contains(fn($salesUserId) => $leaderboardMap->has((int) $salesUserId));
                    })
                    ->count();

                return [
                    'id' => $id,
                    'name' => (string) ($t->full_name ?: $t->name),
                    'health_manager_name' => $healthManagerNames->get($id),
                    'units' => (int) $units,
                    'active_hp' => $activeHealthPlanners,
                    'first_key_in_at' => $firstKeyInAt,
                    'first_key_in_sort' => $firstKeyInAt ?? '9999-12-31 23:59:59',
                    'first_install_date' => $firstInstallDate,
                    'first_install_date_sort' => $firstInstallDate ?? '9999-12-31',
                ];
            })
            ->filter(fn($row) => $row['units'] > 0)
            ->sortBy([
                ['units', 'desc'],
                ['first_key_in_sort', 'asc'],
                ['first_install_date_sort', 'asc'],
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
