<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecordHealthManagerPeriod extends Command
{
    protected $signature = 'users:record-hm-period {user : User ID, email, or DST code} {--ended-on= : Demotion date YYYY-MM-DD} {--started-on= : HM start date; defaults to saved hm_since}';
    protected $description = 'Record a confirmed historical HM period for a demoted user';

    public function handle(): int
    {
        $key = $this->argument('user');
        $matches = User::where(function ($q) use ($key) {
            $q->where('email', $key)->orWhere('dst_code', $key);
            if (ctype_digit($key)) $q->orWhere('id', (int) $key);
        })->get();
        if ($matches->count() !== 1) {
            $this->error('User must match exactly one ID, email, or DST code.');
            return self::FAILURE;
        }
        $user = $matches->first();
        $start = $this->option('started-on') ?: $user->hm_since?->toDateString();
        $end = $this->option('ended-on');
        foreach ([$start, $end] as $date) {
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
                $this->error('Provide valid --started-on and --ended-on dates (YYYY-MM-DD).');
                return self::FAILURE;
            }
        }
        if ($start > $end || Carbon::parse($end)->gt(today())) {
            $this->error('Dates must satisfy: started-on <= ended-on <= today.');
            return self::FAILURE;
        }
        $saved = DB::transaction(function () use ($user, $start, $end) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $periods = DB::table('health_manager_periods')->where('user_id', $user->id);
            if ((clone $periods)->where('started_on', $start)->where('ended_on', $end)->exists()) return true;
            // Do not silently overwrite an existing period or overlap another tenure.
            if ((clone $periods)->where(function ($q) use ($start, $end) {
                $q->where('started_on', $start)->orWhere(function ($q) use ($start, $end) {
                    $q->where('started_on', '<', $end)->where(function ($q) use ($start) {
                        $q->whereNull('ended_on')->orWhere('ended_on', '>', $start);
                    });
                });
            })->exists()) return false;
            DB::table('health_manager_periods')->insert(['user_id' => $user->id, 'started_on' => $start, 'ended_on' => $end]);
            return true;
        });
        if (!$saved) {
            $this->error('Conflicting HM history exists; no changes made.');
            return self::FAILURE;
        }
        $this->info("HM period recorded for {$user->name}: {$start} to {$end}. Demotion applies from the start of its month.");
        return self::SUCCESS;
    }
}
