<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BirthdayGreetings
{
    public function isReady(): bool
    {
        return filled(config('birthday-greetings.gateway_url'))
            && filled(config('birthday-greetings.gateway_token'));
    }

    public function phone(?string $value): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $value);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return preg_match('/^628[0-9]{7,12}$/', $phone) ? '+'.$phone : null;
    }

    public function role(User $user): string
    {
        return $user->hasRole('Health Manager') ? 'Health Manager' : 'Health Planner';
    }

    public function message(User $user, string $customerName): string
    {
        $name = $user->full_name ?: $user->name;
        $phone = $this->phone($user->phone_number) ?: $user->phone_number;

        return "Selamat ulang tahun, {$customerName} 🎉\n"
            ."Semoga sehat, bahagia, dan penuh berkat di usia yang baru.\n\n"
            ."Salam hangat,\n{$name}\n{$this->role($user)} Coway\n{$phone}\n\n"
            .'Pesan ini dikirim melalui LetsGrowAcademy.';
    }

    public function eligible(Customer $customer): bool
    {
        return $customer->date_of_birth !== null && $this->phone($customer->phone_number) !== null;
    }

    public function nextBirthday(Customer $customer, CarbonImmutable $today): ?CarbonImmutable
    {
        if (! $customer->date_of_birth) {
            return null;
        }

        $date = $customer->date_of_birth;
        $year = $today->year;
        // February 29 birthdays are observed on February 28 in non-leap years.
        $day = min($date->day, CarbonImmutable::create($year, $date->month, 1)->daysInMonth);
        $next = CarbonImmutable::create($year, $date->month, $day, 0, 0, 0, $today->timezone);
        if ($next->lt($today->startOfDay())) {
            $year++;
            $day = min($date->day, CarbonImmutable::create($year, $date->month, 1)->daysInMonth);
            $next = CarbonImmutable::create($year, $date->month, $day, 0, 0, 0, $today->timezone);
        }

        return $next;
    }

    public function send(User $user, Customer $customer, string $date): string
    {
        if (! $this->isReady()) {
            throw new RuntimeException('Birthday WhatsApp gateway is not configured.');
        }

        $response = Http::acceptJson()->asJson()
            ->withToken(config('birthday-greetings.gateway_token'))
            ->withHeaders(['Idempotency-Key' => "birthday-{$customer->id}-{$date}"])
            ->connectTimeout(5)->timeout(20)
            ->post(config('birthday-greetings.gateway_url'), [
                'from' => config('birthday-greetings.sender'),
                'to' => $this->phone($customer->phone_number),
                'message' => $this->message($user, $customer->full_name),
            ]);

        $response->throw();
        if ($response->json('success') !== true || ! is_string($response->json('message_id')) || blank($response->json('message_id'))) {
            throw new RuntimeException('Birthday WhatsApp gateway did not acknowledge the message.');
        }

        return $response->json('message_id');
    }
}
