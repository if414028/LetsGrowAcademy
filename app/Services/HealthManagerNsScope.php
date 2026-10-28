<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthManagerNsScope
{
    public static function resolve(User $healthManager): array
    {
        $currentTeamIds = $healthManager->teamUserIds();
        // Promotion moves planner assignments to the new HM. Keep referral
        // descendants available so their pre-promotion NS can stay with the upline.
        $downlineIds = $currentTeamIds->merge($healthManager->downlineUserIds())
            ->unique()->reject(fn ($id) => (int) $id === (int) $healthManager->id)->values();
        $candidateIds = $downlineIds->concat([(int) $healthManager->id]);
        $periods = DB::table('health_manager_periods')->whereIn('user_id', $candidateIds)->get()->groupBy('user_id');
        $ranges = [];
        foreach ($periods as $id => $rows) {
            $ranges[$id] = $rows->map(fn ($row) => [
                'start' => Carbon::parse($row->started_on)->startOfMonth()->toDateString(),
                'end' => $row->ended_on ? Carbon::parse($row->ended_on)->startOfMonth()->toDateString() : null,
            ])->all();
        }
        // Compatibility for HM accounts created outside the user-management flow.
        foreach (User::role('Health Manager')->whereIn('id', $candidateIds)->get() as $hm) {
            if (!isset($ranges[$hm->id])) {
                $ranges[$hm->id] = [[
                    'start' => ($hm->hm_since ?? $hm->join_date ?? $hm->created_at)->copy()->startOfMonth()->toDateString(),
                    'end' => null,
                ]];
            }
        }
        $ownPeriods = $ranges[$healthManager->id] ?? [];
        unset($ranges[$healthManager->id]);
        $children = DB::table('user_hierarchies')->whereIn('child_user_id', $downlineIds)->get()->groupBy('parent_user_id');
        $exclusions = [];
        $managerAncestors = [];
        $visited = [];
        $walk = function ($id, array $inherited = [], array $managerIds = []) use (&$walk, &$visited, &$exclusions, &$managerAncestors, $children, $ranges) {
            if (isset($visited[$id])) return;
            $visited[$id] = true;
            foreach ($children->get($id, collect()) as $edge) {
                $child = (int) $edge->child_user_id;
                $combined = array_merge($inherited, $ranges[$child] ?? []);
                $childManagerIds = isset($ranges[$child]) ? array_merge($managerIds, [$child]) : $managerIds;
                $exclusions[$child] = $combined;
                $managerAncestors[$child] = $childManagerIds;
                $walk($child, $combined, $childManagerIds);
            }
        };
        $walk((int) $healthManager->id);
        $assignedPlanners = User::role('Health Planner')->whereIn('id', $downlineIds)
            ->whereNotNull('health_manager_id')->get(['id', 'health_manager_id']);
        foreach ($assignedPlanners as $planner) {
            if ((int) $planner->health_manager_id === (int) $healthManager->id) {
                // A direct assignment still overrides a different referral branch.
                unset($exclusions[$planner->id]);
            } elseif (! $currentTeamIds->contains($planner->id)
                && ! in_array((int) $planner->health_manager_id, $managerAncestors[$planner->id] ?? [], true)) {
                // Ordinary transfers to another branch keep their explicit owner.
                $downlineIds = $downlineIds->reject(fn ($id) => (int) $id === (int) $planner->id);
                unset($exclusions[$planner->id]);
            }
        }
        $scopeIds = $downlineIds->concat([(int) $healthManager->id])->unique()->values();
        return ['scope_ids' => $scopeIds, 'exclusion_periods' => $exclusions, 'hm_periods' => $ranges, 'own_periods' => $ownPeriods];
    }

    public static function excluded(array $periods, string $month): bool
    {
        foreach ($periods as $period) {
            if ($month >= $period['start'] && ($period['end'] === null || $month < $period['end'])) return true;
        }
        return false;
    }

    public static function apply($query, User $healthManager, string $salesOrderAlias, string $activityDateSql): array
    {
        $scope = self::resolve($healthManager);
        $query->whereIn("{$salesOrderAlias}.sales_user_id", $scope['scope_ids']);
        if ($scope['own_periods']) {
            // Pre-promotion NS belongs to the former HM, never to both HM rows.
            $query->where(function ($q) use ($scope, $activityDateSql) {
                foreach ($scope['own_periods'] as $period) {
                    $q->orWhere(function ($interval) use ($period, $activityDateSql) {
                        $interval->whereRaw("DATE({$activityDateSql}) >= ?", [$period['start']]);
                        if ($period['end'] !== null) $interval->whereRaw("DATE({$activityDateSql}) < ?", [$period['end']]);
                    });
                }
            });
        }
        foreach ($scope['exclusion_periods'] as $id => $periods) {
            foreach ($periods as $period) {
                // Outside each HM interval, this seller can contribute to the parent again.
                $query->where(function ($q) use ($id, $period, $salesOrderAlias, $activityDateSql) {
                    $q->where("{$salesOrderAlias}.sales_user_id", '!=', $id)
                        ->orWhereRaw("DATE({$activityDateSql}) < ?", [$period['start']]);
                    if ($period['end'] !== null) $q->orWhereRaw("DATE({$activityDateSql}) >= ?", [$period['end']]);
                });
            }
        }
        return $scope;
    }
}
