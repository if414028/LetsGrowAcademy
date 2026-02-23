<?php

namespace App\Observers;

use App\Models\Contest;
use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Support\Facades\Log;

class UserHierarchyObserver
{
    public function created(UserHierarchy $hierarchy): void
    {
        Log::info('UserHierarchyObserver fired', [
            'parent' => $hierarchy->parent_user_id,
            'child'  => $hierarchy->child_user_id,
            'id'     => $hierarchy->id,
        ]);

        $childId  = (int) $hierarchy->child_user_id;
        $parentId = (int) $hierarchy->parent_user_id;

        // ambil kontes aktif yang diikuti parent
        $now = now();

        $contests = Contest::query()
            ->where('status', 'active')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->whereHas('participants', fn($p) => $p->where('users.id', $parentId))
            ->get();

        Log::info('Contest count', [
            'count' => $contests->count(),
            'parent' => $parentId
        ]);

        foreach ($contests as $contest) {
            $contest->participants()->syncWithoutDetaching([
                $childId => [
                    'joined_at' => now(),
                    'status' => 'active',
                ],
            ]);
        }
    }
}
