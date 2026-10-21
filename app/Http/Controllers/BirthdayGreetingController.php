<?php

namespace App\Http\Controllers;

use App\Models\BirthdayGreetingRecipient;
use App\Models\BirthdayGreetingSetting;
use App\Models\Customer;
use App\Models\User;
use App\Services\BirthdayGreetings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BirthdayGreetingController extends Controller
{
    public function index(Request $request, BirthdayGreetings $greetings)
    {
        $user = $request->user();
        $setting = BirthdayGreetingSetting::where('user_id', $user->id)->first();
        $today = CarbonImmutable::now(config('birthday-greetings.timezone'))->startOfDay();
        $selected = BirthdayGreetingRecipient::where('user_id', $user->id)->pluck('customer_id');
        $customers = Customer::where('health_planner_id', $user->id)->get()
            ->sortBy(fn ($customer) => ($greetings->nextBirthday($customer, $today)?->format('Y-m-d') ?? '9999').$customer->full_name)
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'phone' => $customer->phone_number,
                'birthday' => $customer->date_of_birth?->locale('id')->translatedFormat('d F'),
                'next_birthday' => $greetings->nextBirthday($customer, $today)?->locale('id')->translatedFormat('d F Y'),
                'eligible' => $greetings->eligible($customer),
                'reason' => ! $customer->date_of_birth ? 'Tanggal lahir belum diisi' : (! $greetings->phone($customer->phone_number) ? 'Nomor WhatsApp belum valid' : null),
                'selected' => $selected->contains($customer->id),
                'message' => $greetings->message($user, $customer->full_name),
            ])->values();

        return view('birthday-greetings.index', [
            'setting' => $setting,
            'customers' => $customers,
            'gatewayReady' => $greetings->isReady(),
            'profileReady' => (bool) $greetings->phone($user->phone_number),
            'sender' => config('birthday-greetings.sender'),
            'sampleMessage' => $greetings->message($user, 'Nama Customer'),
        ]);
    }

    public function activate(Request $request, BirthdayGreetings $greetings)
    {
        $this->validateProfile($request->user(), $greetings);
        BirthdayGreetingSetting::updateOrCreate(['user_id' => $request->user()->id], [
            'enabled' => true,
            'activated_at' => now(),
        ]);

        return to_route('birthday-greetings.index')->with('success', 'Birthday Greetings diaktifkan. Pilih customer penerima ucapan Anda.');
    }

    public function update(Request $request, BirthdayGreetings $greetings)
    {
        $data = $request->validate([
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $data['customer_ids'] ??= [];
        $user = $request->user();

        DB::transaction(function () use ($data, $user, $greetings) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $setting = BirthdayGreetingSetting::where('user_id', $user->id)->first();
            abort_unless($setting?->enabled, 403);
            $customers = Customer::where('health_planner_id', $user->id)
                ->whereIn('id', $data['customer_ids'])->lockForUpdate()->get();
            if ($customers->count() !== count($data['customer_ids']) || $customers->contains(fn ($customer) => ! $greetings->eligible($customer))) {
                throw ValidationException::withMessages(['customer_ids' => 'Pilih customer milik Anda dengan tanggal lahir dan nomor WhatsApp yang valid.']);
            }

            BirthdayGreetingRecipient::where('user_id', $user->id)->whereNotIn('customer_id', $data['customer_ids'])->delete();
            foreach ($customers as $customer) {
                BirthdayGreetingRecipient::firstOrCreate(['user_id' => $user->id, 'customer_id' => $customer->id]);
            }
        });

        return to_route('birthday-greetings.index')->with('success', 'Daftar penerima Birthday Greetings berhasil disimpan.');
    }

    public function deactivate(Request $request)
    {
        BirthdayGreetingSetting::where('user_id', $request->user()->id)->update(['enabled' => false]);

        return to_route('birthday-greetings.index')->with('success', 'Birthday Greetings dinonaktifkan. Pilihan customer tetap tersimpan.');
    }

    private function validateProfile(User $user, BirthdayGreetings $greetings): void
    {
        if (! $greetings->phone($user->phone_number)) {
            throw ValidationException::withMessages(['profile' => 'Lengkapi nomor WhatsApp yang valid pada profil Anda sebelum mengaktifkan Birthday Greetings.']);
        }
    }
}
