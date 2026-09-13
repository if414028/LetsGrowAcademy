<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthManagerNsScope
{
    public static function resolve(User $healthManager): array
    {
        $downlineIds = $healthManager->downlineUserIds()->unique()->reject(fn ($id) => (int) $id === (int) $healthManager->id)->values();
        $scopeIds = $downlineIds->concat([(int) $healthManager->id])->unique()->values();
        $periods = DB::table('health_manager_periods')->whereIn('user_id', $downlineIds)->get()->groupBy('user_id');
        $ranges = [];
        foreach ($periods as $id => $rows) {
            $ranges[$id] = $rows->map(fn ($row) => [
                'start' => Carbon::parse($row->started_on)->startOfMonth()->toDateString(),
                'end' => $row->ended_on ? Carbon::parse($row->ended_on)->startOfMonth()->toDateString() : null,
            ])->all();
        }
        // Compatibility for HM accounts created outside the user-management flow.
        foreach (User::role('Health Manager')->whereIn('id', $downlineIds)->get() as $hm) {
            if (!isset($ranges[$hm->id])) {
                $ranges[$hm->id] = [[
                    'start' => ($hm->hm_since ?? $hm->join_date ?? $hm->created_at)->copy()->startOfMonth()->toDateString(),
                    'end' => null,
                ]];
            }
        }
        $children = DB::table('user_hierarchies')->whereIn('child_user_id', $downlineIds)->get()->groupBy('parent_user_id');
        $exclusions = [];
        $visited = [];
        $walk = function ($id, array $inherited = []) use (&$walk, &$visited, &$exclusions, $children, $ranges) {
            if (isset($visited[$id])) return;
            $visited[$id] = true;
            foreach ($children->get($id, collect()) as $edge) {
                $child = (int) $edge->child_user_id;
                $combined = array_merge($inherited, $ranges[$child] ?? []);
                $exclusions[$child] = $combined;
                $walk($child, $combined);
            }
        };
        $walk((int) $healthManager->id);
        return ['scope_ids' => $scopeIds, 'exclusion_periods' => $exclusions, 'hm_periods' => $ranges];
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
