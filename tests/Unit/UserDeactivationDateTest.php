<?php

namespace Tests\Unit;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserDeactivationDateTest extends TestCase
{
    public function test_status_transitions_record_clear_and_refresh_deactivation_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 10:00:00'));
        try {
            $user = new User;
            $user->setRawAttributes(['status' => 'Active', 'deactivated_at' => null], true);
            $observer = new UserObserver;

            $user->status = 'Inactive';
            $observer->saving($user);
            $this->assertSame('2026-09-13 10:00:00', $user->deactivated_at->format('Y-m-d H:i:s'));
            $user->syncOriginal();

            Carbon::setTestNow(Carbon::parse('2026-09-14 12:00:00'));
            $user->name = 'Updated name';
            $observer->saving($user);
            $this->assertSame('2026-09-13 10:00:00', $user->deactivated_at->format('Y-m-d H:i:s'));

            $user->status = 'Active';
            $observer->saving($user);
            $this->assertNull($user->deactivated_at);
            $user->syncOriginal();

            $user->status = 'Inactive';
            $observer->saving($user);
            $this->assertSame('2026-09-14 12:00:00', $user->deactivated_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
