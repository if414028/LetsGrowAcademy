<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contest;
use Illuminate\Support\Carbon;

class EndExpiredContests extends Command
{
    protected $signature = 'contests:end-expired';
    protected $description = 'End contests whose period has passed';

    public function handle(): int
    {
        $today = Carbon::today();

        $endedCount = Contest::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'ended',
                'updated_at' => now(),
            ]);

        $this->info("Expired contests ended: {$endedCount}");

        return self::SUCCESS;
    }
}
