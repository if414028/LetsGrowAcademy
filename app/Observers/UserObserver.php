<?php

namespace App\Observers;

use App\Models\{Customer, User};
use App\Services\CustomerOwnership;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    public function saving(User $user): void
    {
        // Sebagian test lama membuat tabel users minimal tanpa menjalankan semua migration.
        if (Schema::hasColumn($user->getTable(), 'landing_page_slug')
            && (!$user->landing_page_slug || $user->isDirty(['name', 'dst_code']))) {
            $user->landing_page_slug = User::uniqueLandingPageSlug(
                collect([$user->name ?: 'member', $user->dst_code])->filter()->implode('-'),
                $user->exists ? $user->id : null,
            );
        }

        if ($user->isDirty('status')) {
            $user->deactivated_at = strcasecmp((string) $user->status, 'Inactive') === 0
                ? now()
                : null;
        }
    }

    public function updating(User $user): void
    {
        if (!$this->becomingInactivePlanner($user)) return;
        if (Customer::where('health_planner_id', $user->id)->exists()
            && !app(CustomerOwnership::class)->nearestManager($user)) {
            throw ValidationException::withMessages([
                'status' => 'HP ini memiliki customer tetapi belum memiliki HM dalam hierarki. Atur HM terlebih dahulu sebelum menonaktifkan HP.',
            ]);
        }
    }

    public function updated(User $user): void
    {
        if (!$this->becomingInactivePlanner($user)) return;
        $manager = app(CustomerOwnership::class)->nearestManager($user);
        if ($manager) {
            Customer::where('health_planner_id', $user->id)->update(['health_planner_id' => $manager->id]);
        }
    }

    private function becomingInactivePlanner(User $user): bool
    {
        return $user->isDirty('status') && strcasecmp((string) $user->status, 'Inactive') === 0
            && $user->hasRole('Health Planner');
    }
}
