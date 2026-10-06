<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class CustomerOwnership
{
    // Call inside the same transaction as the customer write. Locking the owner
    // serializes concurrent assignments, including assignments from sales orders.
    public function validateOwner(int $ownerId, string $field = 'health_planner_id'): User
    {
        $owner = User::query()->lockForUpdate()->findOrFail($ownerId);
        if (!$owner->hasAnyRole(['Health Planner', 'Health Manager']) || strcasecmp((string) $owner->status, 'Active') !== 0) {
            throw ValidationException::withMessages([$field => 'Pemilik harus Health Planner atau Health Manager yang aktif.']);
        }
        return $owner;
    }

    public function nearestManager(User $hp): ?User
    {
        $manager = HealthManagerAssignment::managerFor($hp);
        return $manager?->hasRole('Health Manager') ? $manager : null;
    }
}
