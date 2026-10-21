<?php

namespace App\Console\Commands;

use App\Models\BirthdayGreetingRecipient;
use App\Models\BirthdayGreetingSetting;
use App\Services\BirthdayGreetings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBirthdayGreetings extends Command
{
    protected $signature = 'birthday-greetings:send';

    protected $description = 'Send opted-in birthday greetings for active HP/HM subscribers';

    public function handle(BirthdayGreetings $greetings): int
    {
        if (! $greetings->isReady()) {
            $this->info('Birthday Greetings menunggu konfigurasi layanan WhatsApp.');

            return self::SUCCESS;
        }

        $today = CarbonImmutable::now(config('birthday-greetings.timezone'));
        if ($today->hour < config('birthday-greetings.send_hour')) {
            return self::SUCCESS;
        }
        $date = $today->toDateString();
        $failed = false;

        BirthdayGreetingRecipient::query()
            ->whereHas('customer', function ($query) use ($today) {
                $query->whereMonth('date_of_birth', $today->month)
                    ->where(function ($query) use ($today) {
                        $query->whereDay('date_of_birth', $today->day);
                        if (! $today->isLeapYear() && $today->month === 2 && $today->day === 28) {
                            $query->orWhereDay('date_of_birth', 29);
                        }
                    });
            })->chunkById(100, function ($recipients) use ($greetings, $date, &$failed) {
                foreach ($recipients as $recipient) {
                    // Earlier messages in this batch may take time. Reload ownership,
                    // account status, and selection immediately before each delivery.
                    $recipient = BirthdayGreetingRecipient::with(['user.roles', 'customer'])->find($recipient->id);
                    if (! $recipient) {
                        continue;
                    }
                    $user = $recipient->user;
                    $customer = $recipient->customer;
                    if (! $user || ! $customer || (int) $customer->health_planner_id !== (int) $user->id
                        || $user->status !== 'Active' || ! $user->hasAnyRole(['Health Planner', 'Health Manager'])
                        || ! $user->hasActiveSubscription() || ! $greetings->phone($user->phone_number)
                        || ! $greetings->eligible($customer)
                        || ! BirthdayGreetingSetting::where('user_id', $user->id)->where('enabled', true)->exists()) {
                        continue;
                    }

                    $key = ['customer_id' => $customer->id, 'greeting_date' => $date];
                    $claimed = DB::table('birthday_greeting_deliveries')->insertOrIgnore($key + [
                        'user_id' => $user->id, 'status' => 'processing', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if (! $claimed) {
                        $claimed = DB::table('birthday_greeting_deliveries')->where($key)->where(function ($query) {
                            $query->where('status', 'failed')->orWhere(function ($query) {
                                $query->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(30));
                            });
                        })
                            ->update(['status' => 'processing', 'user_id' => $user->id, 'updated_at' => now()]);
                    }
                    if (! $claimed) {
                        continue;
                    }

                    try {
                        $providerId = $greetings->send($user, $customer, $date);
                        DB::table('birthday_greeting_deliveries')->where($key)->update([
                            'status' => 'sent', 'provider_message_id' => mb_substr($providerId, 0, 255),
                            'sent_at' => now(), 'updated_at' => now(),
                        ]);
                    } catch (Throwable $exception) {
                        DB::table('birthday_greeting_deliveries')->where($key)->update(['status' => 'failed', 'updated_at' => now()]);
                        // Do not put customer phone numbers, message bodies, tokens, or gateway responses in logs.
                        Log::warning('Birthday greeting delivery failed.', ['customer_id' => $customer->id, 'date' => $date, 'error_type' => $exception::class]);
                        $failed = true;
                    }
                }
            });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
