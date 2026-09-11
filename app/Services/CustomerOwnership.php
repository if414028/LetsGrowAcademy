<?php

namespace App\Services;

use App\Models\{Customer, User, UserHierarchy};
use Illuminate\Validation\ValidationException;

class CustomerOwnership
{
    // Call inside the same transaction as the customer write. Locking the owner
    // serializes concurrent assignments, including assignments from sales orders.
    public function validateOwner(int $ownerId, ?int $customerId = null, string $field = 'health_planner_id'): User
    {
        $owner = User::query()->lockForUpdate()->findOrFail($ownerId);
        if (!$owner->hasAnyRole(['Health Planner', 'Health Manager']) || strcasecmp((string) $owner->status, 'Active') !== 0) {
            throw ValidationException::withMessages([$field => 'Pemilik harus Health Planner atau Health Manager yang aktif.']);
        }
        if ($owner->hasRole('Health Planner') && !$owner->hasRole('Health Manager')
            && Customer::where('health_planner_id', $ownerId)
                ->when($customerId, fn ($q) => $q->where('id', '!=', $customerId))->exists()) {
            throw ValidationException::withMessages([$field => 'Health Planner ini sudah memiliki customer. Satu HP maksimal memiliki satu customer.']);
        }
        return $owner;
    }

    public function nearestManager(User $hp): ?User
    {
        $visited = [$hp->id];
        $current = $hp->id;
        while ($parentId = UserHierarchy::where('child_user_id', $current)->value('parent_user_id')) {
            if (in_array((int) $parentId, $visited, true)) return null;
            $visited[] = (int) $parentId;
            $parent = User::find($parentId);
            if (!$parent) return null;
            if ($parent->hasRole('Health Manager')) return $parent;
            $current = $parent->id;
        }
        return null;
    }
}
