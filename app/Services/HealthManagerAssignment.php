<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HealthManagerAssignment
{
    public static function managerFor(User $user): ?User
    {
        // Keep the saved identity even if the former HM has since become an SM.
        if ($user->hasRole('Health Planner') && $user->health_manager_id !== null) {
            return $user->assignedHealthManager;
        }

        return self::fromHierarchy((int) $user->id);
    }

    public static function fromHierarchy(int $userId): ?User
    {
        $seen = [$userId => true];
        while ($parentId = UserHierarchy::where('child_user_id', $userId)->value('parent_user_id')) {
            $parentId = (int) $parentId;
            if (isset($seen[$parentId])) return null;
            $seen[$parentId] = true;
            $parent = User::find($parentId);
            if (! $parent) return null;
            if ($parent->hasRole('Health Manager')) return $parent;
            $userId = $parentId;
        }

        return null;
    }

    public static function fromReferrer(User $referrer): ?User
    {
        return $referrer->hasRole('Health Manager') ? $referrer : self::managerFor($referrer);
    }

    public static function teamUserIds(User $manager): Collection
    {
        $referralIds = $manager->downlineUserIds()->unique();
        $managerIds = $manager->hasRole('Sales Manager')
            ? User::role('Health Manager')->whereIn('id', $referralIds)->pluck('id')->push($manager->id)
            : collect([$manager->id]);
        $assigned = User::role('Health Planner')->whereNotNull('health_manager_id')
            ->where(function ($query) use ($managerIds, $referralIds) {
                $query->whereIn('health_manager_id', $managerIds)->orWhereIn('id', $referralIds);
            })->get(['id', 'health_manager_id']);
        $outgoing = $assigned->whereNotIn('health_manager_id', $managerIds->all())->pluck('id');
        $incoming = $assigned->whereIn('health_manager_id', $managerIds->all())->pluck('id');

        return $referralIds->diff($outgoing)->merge($incoming)->unique()
            ->reject(fn ($id) => (int) $id === (int) $manager->id)->values();
    }

    public static function plannersForTransfer(User $source): Collection
    {
        $candidateIds = $source->downlineUserIds();
        return User::role('Health Planner')->where(function ($query) use ($source, $candidateIds) {
            $query->where('health_manager_id', $source->id)
                ->orWhere(function ($query) use ($candidateIds) {
                    $query->whereNull('health_manager_id')->whereIn('id', $candidateIds);
                });
        })->get()->filter(function ($planner) use ($source) {
            $manager = self::managerFor($planner);
            return (int) $manager?->id === (int) $source->id || $manager === null;
        })->values();
    }

    public static function transferPlanners(User $source, User $destination, bool $dryRun = false): Collection
    {
        return DB::transaction(function () use ($source, $destination, $dryRun) {
            $locked = User::whereIn('id', [$source->id, $destination->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $locked->get($source->id);
            $destination = $locked->get($destination->id);
            if (! $source?->hasAnyRole(['Health Manager', 'Sales Manager'])) {
                throw ValidationException::withMessages(['source' => 'Sumber harus Health Manager atau Sales Manager.']);
            }
            if (! $destination?->hasRole('Health Manager') || $destination->status !== 'Active') {
                throw ValidationException::withMessages(['health_manager_id' => 'Pilih Health Manager yang aktif.']);
            }
            if ($source->id === $destination->id) {
                throw ValidationException::withMessages(['health_manager_id' => 'Pilih HM tujuan yang berbeda.']);
            }

            $planners = self::plannersForTransfer($source)->sortBy('name')->values();
            if (! $dryRun && $planners->isNotEmpty()) {
                User::role('Health Planner')->whereIn('id', $planners->pluck('id'))
                    ->update(['health_manager_id' => $destination->id]);
            }

            return $planners;
        });
    }
}
