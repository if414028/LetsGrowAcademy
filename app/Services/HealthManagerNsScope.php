<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HealthManagerNsScope
{
    /**
     * Return all sellers in an HM tree and the month from which promoted-HM
     * subtrees must stop contributing to the original HM.
     */
    public static function resolve(User $healthManager): array
    {
        $downlineIds = $healthManager->downlineUserIds()->unique()->values();
        $scopeIds = $downlineIds->concat([(int) $healthManager->id])->unique()->values();

        $promotedHmMonths = User::query()
            ->whereIn('id', $downlineIds)
            ->role('Health Manager')
            ->get(['id', 'hm_since', 'join_date', 'created_at'])
            ->mapWithKeys(function (User $user) {
                $promotionDate = $user->hm_since ?? $user->join_date ?? $user->created_at;

                return [(int) $user->id => $promotionDate->copy()->startOfMonth()->toDateString()];
            });

        $childrenByParent = DB::table('user_hierarchies')
            ->whereIn('child_user_id', $downlineIds)
            ->get(['parent_user_id', 'child_user_id'])
            ->groupBy('parent_user_id');

        $exclusionMonthByUser = [];
        $visited = [];
        $walk = function (int $userId, ?string $inheritedCutoff = null) use (
            &$walk,
            &$exclusionMonthByUser,
            &$visited,
            $childrenByParent,
            $promotedHmMonths
        ): void {
            if (isset($visited[$userId])) {
                return;
            }
            $visited[$userId] = true;

            foreach ($childrenByParent->get($userId, collect()) as $edge) {
                $childId = (int) $edge->child_user_id;
                $promotionMonth = $promotedHmMonths->get($childId);
                $cutoff = $inheritedCutoff;

                if ($promotionMonth !== null && ($cutoff === null || $promotionMonth < $cutoff)) {
                    $cutoff = $promotionMonth;
                }

                if ($cutoff !== null) {
                    $exclusionMonthByUser[$childId] = $cutoff;
                }

                $walk($childId, $cutoff);
            }
        };
        $walk((int) $healthManager->id);

        return [
            'scope_ids' => $scopeIds,
            'exclusion_months' => collect($exclusionMonthByUser),
            'promoted_hm_months' => $promotedHmMonths,
        ];
    }

    /**
     * Apply the historical HM ownership rule to a sales-order query.
     * $activityDateSql may be a column or SQL expression such as COALESCE(...).
     */
    public static function apply($query, User $healthManager, string $salesOrderAlias, string $activityDateSql): array
    {
        $scope = self::resolve($healthManager);
        /** @var Collection $exclusionMonths */
        $exclusionMonths = $scope['exclusion_months'];
        $restrictedIds = $exclusionMonths->keys()->map(fn($id) => (int) $id);
        $unrestrictedIds = $scope['scope_ids']->diff($restrictedIds)->values();

        $query->where(function ($ownership) use ($salesOrderAlias, $activityDateSql, $unrestrictedIds, $exclusionMonths) {
            if ($unrestrictedIds->isNotEmpty()) {
                $ownership->whereIn("{$salesOrderAlias}.sales_user_id", $unrestrictedIds->all());
            }

            foreach ($exclusionMonths->groupBy(fn($month) => $month, true) as $month => $userCutoffs) {
                $ownership->orWhere(function ($branch) use ($salesOrderAlias, $activityDateSql, $month, $userCutoffs) {
                    $branch->whereIn("{$salesOrderAlias}.sales_user_id", $userCutoffs->keys()->map(fn($id) => (int) $id)->all())
                        ->whereRaw("DATE({$activityDateSql}) < ?", [$month]);
                });
            }
        });

        return $scope;
    }
}
