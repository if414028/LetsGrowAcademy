<?php

namespace App\Console\Commands;

use App\Services\SalesActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeactivateInactivePlanners extends Command
{
    protected $signature = 'users:deactivate-inactive-planners';
    protected $description = 'Deactivate Health Planners on their estimated inactivity date';

    public function handle(): int
    {
        $today = today();
        $deactivated = 0;
        $blocked = 0;

        SalesActivity::planners()->chunkById(200, function ($users) use ($today, &$deactivated, &$blocked) {
            foreach ($users as $candidate) {
                if (SalesActivity::deactivateAt($candidate)->gt($today)) {
                    continue;
                }

                try {
                    $changed = DB::transaction(function () use ($candidate, $today) {
                        $user = SalesActivity::planners()->whereKey($candidate->id)->lockForUpdate()->first();
                        if (!$user || SalesActivity::deactivateAt($user)->gt($today)) {
                            return false;
                        }
                        // Model events transfer customers to the nearest Health Manager.
                        $user->update(['status' => 'Inactive']);
                        return true;
                    });
                    $deactivated += (int) $changed;
                } catch (ValidationException $e) {
                    $blocked++;
                    Log::warning('Automatic planner deactivation blocked', [
                        'user_id' => $candidate->id, 'errors' => $e->errors(),
                    ]);
                    $this->warn("User {$candidate->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Deactivated: {$deactivated}; blocked: {$blocked}");
        return $blocked ? self::FAILURE : self::SUCCESS;
    }
}
