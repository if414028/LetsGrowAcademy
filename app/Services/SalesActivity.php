<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SalesActivity
{
    public static function planners(): Builder
    {
        return User::query()->role('Health Planner')->where('users.status', 'Active')
            ->withMax(['salesOrders as last_install_at' => function ($query) {
                $query->where('status', 'selesai')->whereNotNull('install_date');
            }], 'install_date');
    }

    public static function lastActivity(User $user): Carbon
    {
        return Carbon::parse($user->last_install_at ?? $user->created_at)->startOfDay();
    }

    public static function deactivateAt(User $user): Carbon
    {
        // Keep the same six-month calculation as the dashboard estimate.
        return self::lastActivity($user)->addMonths(6);
    }
}
