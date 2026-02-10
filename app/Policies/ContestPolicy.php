<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        // creator boleh
        if ((int) $contest->created_by_user_id === (int) $user->id) {
            return true;
        }

        // participant boleh (HP/HM)
        if ($contest->participants()->where('users.id', $user->id)->exists()) {
            return true;
        }

        // ✅ Sales Manager boleh lihat kalau ada HM downline dia yang jadi participant kontes ini
        if ($user->hasRole('Sales Manager')) {
            return $contest->participants()
                ->whereHas('roles', fn($r) => $r->where('name', 'Health Manager'))
                ->whereIn('users.id', function ($q) use ($user) {
                    $q->select('child_user_id')
                        ->from('user_hierarchies') // pastikan ini bener
                        ->where('parent_user_id', $user->id);
                })
                ->exists();
        }

        return false;
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
        return $contest->created_by_user_id === $user->id
            || $user->hasAnyRole(['Admin', 'Head Admin']);
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
