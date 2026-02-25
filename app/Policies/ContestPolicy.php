<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ContestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Admin',
            'Head Admin',
            'Sales Manager',
            'Health Manager',
            'Health Planner',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contest $contest): bool
    {
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            return true;
        }

        // creator selalu boleh lihat
        if ((int)$contest->created_by_user_id === (int)$user->id) {
            return true;
        }

        // selain creator: hanya boleh lihat kontes active/ended yang relevan
        if (!in_array($contest->status, ['active', 'ended'], true)) {
            return false;
        }

        $rules = (array) ($contest->rules ?? []);
        $targetHmIds = array_map('intval', (array)($rules['target_hm_ids'] ?? []));

        if ($user->hasRole('Sales Manager')) {
            // SM boleh lihat jika ada HM bawahannya yang termasuk target
            $hmIds = DB::table('user_hierarchies')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'user_hierarchies.child_user_id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('user_hierarchies.parent_user_id', $user->id)
                ->where('roles.name', 'Health Manager')
                ->pluck('user_hierarchies.child_user_id')
                ->map(fn($v) => (int)$v)
                ->all();

            return count(array_intersect($hmIds, $targetHmIds)) > 0;
        }

        if ($user->hasRole('Health Manager')) {
            return in_array((int)$user->id, $targetHmIds, true);
        }

        // HP / role lain: harus participant
        return $contest->participants()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'Admin',
            'Head Admin',
            'Sales Manager',
            'Health Manager',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contest $contest): bool
    {
        // kalau sudah aktif / selesai, tidak boleh diubah sama sekali
        if (in_array($contest->status, ['active', 'ended'], true)) {
            return false;
        }

        // hanya creator atau Admin/Head Admin yang boleh edit saat masih draft
        return (int)$contest->created_by_user_id === (int)$user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contest $contest): bool
    {
        return $this->update($user, $contest);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contest $contest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contest $contest): bool
    {
        return false;
    }
}
