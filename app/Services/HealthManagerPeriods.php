<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HealthManagerPeriods
{
    public static function sync(User $user, bool $wasHealthManager): void
    {
        $open = DB::table('health_manager_periods')->where('user_id', $user->id)->whereNull('ended_on');
        if ($user->hasRole('Health Manager')) {
            $start = ($user->hm_since ?? $user->join_date ?? $user->created_at)->toDateString();
            if (DB::table('health_manager_periods')->where('user_id', $user->id)
                ->whereNotNull('ended_on')->where('ended_on', '>', $start)->exists()) {
                throw ValidationException::withMessages(['hm_since' => 'Tanggal mulai HM bertumpang tindih dengan periode HM sebelumnya.']);
            }
            if ($open->exists()) {
                $open->update(['started_on' => $start]);
            } else {
                // Multiple role changes on the same day are one monthly HM interval.
                DB::table('health_manager_periods')->updateOrInsert(
                    ['user_id' => $user->id, 'started_on' => $start],
                    ['ended_on' => null]
                );
            }
        } elseif ($wasHealthManager) {
            if (!$open->exists()) {
                DB::table('health_manager_periods')->insert([
                    'user_id' => $user->id,
                    'started_on' => ($user->hm_since ?? $user->join_date ?? $user->created_at)->toDateString(),
                    'ended_on' => today()->toDateString(),
                ]);
            } else {
                $open->update(['ended_on' => today()->toDateString()]);
            }
        }
    }
}
