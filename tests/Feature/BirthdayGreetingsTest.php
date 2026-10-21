<?php

namespace Tests\Feature;

use App\Models\BirthdayGreetingRecipient;
use App\Models\BirthdayGreetingSetting;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BirthdayGreetings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BirthdayGreetingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(9, 0));
        Http::preventStrayRequests();
        config(['birthday-greetings.gateway_url' => null, 'birthday-greetings.gateway_token' => null]);
    }

    private function member(string $role = 'Health Planner', bool $subscribed = true): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['status' => 'Active', 'full_name' => 'Nadia Putri', 'phone_number' => '081234567890']);
        $user->assignRole($role);
        if ($subscribed) {
            Subscription::create([
                'user_id' => $user->id, 'duration_months' => 1, 'amount' => 50000,
                'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
            ]);
        }

        return $user;
    }

    private function customer(User $owner, array $attributes = []): Customer
    {
        return Customer::create($attributes + [
            'health_planner_id' => $owner->id, 'full_name' => 'Customer Rani',
            'date_of_birth' => '1990-10-20', 'phone_number' => '081298765432',
        ]);
    }

    private function optIn(User $user, Customer $customer): void
    {
        BirthdayGreetingSetting::updateOrCreate(['user_id' => $user->id], ['enabled' => true, 'activated_at' => now()]);
        BirthdayGreetingRecipient::create(['user_id' => $user->id, 'customer_id' => $customer->id]);
    }

    private function gateway(): void
    {
        config(['birthday-greetings.gateway_url' => 'https://gateway.test/messages', 'birthday-greetings.gateway_token' => 'test-token']);
        Http::fake(['gateway.test/*' => Http::response(['success' => true, 'message_id' => 'wa-123'])]);
    }

    public function test_guests_non_subscribers_and_other_roles_cannot_manage_greetings(): void
    {
        $this->get(route('birthday-greetings.index'))->assertRedirect(route('login'));
        $this->post(route('birthday-greetings.activate'))->assertRedirect(route('login'));
        $this->actingAs($this->member(subscribed: false));
        foreach ([['get', 'index'], ['post', 'activate'], ['put', 'update'], ['post', 'deactivate']] as [$method, $action]) {
            $this->{$method}(route('birthday-greetings.'.$action))->assertRedirect(route('subscriptions.index'));
        }
        $this->actingAs($this->member('Sales Manager'))->get(route('birthday-greetings.index'))->assertForbidden();
    }

    public function test_intro_has_illustration_exact_template_and_explicit_activation_without_selecting_anyone(): void
    {
        $user = $this->member();
        $this->customer($user);
        $this->actingAs($user)->get(route('birthday-greetings.index'))->assertOk()
            ->assertSee('Hadir di hari istimewa mereka.')
            ->assertSee('birthday-art-title')->assertSee('+6285121069310')
            ->assertSee('Selamat ulang tahun, Nama Customer 🎉')
            ->assertSee('Health Planner Coway')->assertSee('Pesan ini dikirim melalui LetsGrowAcademy.');
        $this->post(route('birthday-greetings.activate'))->assertRedirect(route('birthday-greetings.index'));
        $this->assertDatabaseHas('birthday_greeting_settings', ['user_id' => $user->id, 'enabled' => true]);
        $this->assertDatabaseCount('birthday_greeting_recipients', 0);
        $this->get(route('birthday-greetings.index'))->assertOk()->assertSee('Customer penerima')
            ->assertSee('Menunggu layanan WhatsApp')->assertDontSee('Hadir di hari istimewa mereka.');
        Http::assertNothingSent();
    }

    public function test_profile_phone_is_required_before_activation(): void
    {
        $user = $this->member();
        $user->update(['phone_number' => null]);
        $this->actingAs($user)->post(route('birthday-greetings.activate'))->assertSessionHasErrors('profile');
        $this->assertDatabaseCount('birthday_greeting_settings', 0);
    }

    public function test_hm_can_only_see_and_choose_directly_owned_customers(): void
    {
        $hm = $this->member('Health Manager');
        $hp = $this->member();
        DB::table('user_hierarchies')->insert(['parent_user_id' => $hm->id, 'child_user_id' => $hp->id]);
        $own = $this->customer($hm, ['full_name' => 'Own Customer']);
        $other = $this->customer($hp, ['full_name' => 'Downline Private Customer']);
        $this->actingAs($hm)->post(route('birthday-greetings.activate'));
        $this->get(route('birthday-greetings.index'))->assertOk()->assertSee('Own Customer')->assertDontSee('Downline Private Customer');
        $this->put(route('birthday-greetings.update'), ['customer_ids' => [$own->id]])->assertSessionHasNoErrors();
        $this->put(route('birthday-greetings.update'), ['customer_ids' => [$other->id]])->assertSessionHasErrors('customer_ids');
        $this->assertDatabaseHas('birthday_greeting_recipients', ['user_id' => $hm->id, 'customer_id' => $own->id]);
        $this->assertDatabaseMissing('birthday_greeting_recipients', ['user_id' => $hm->id, 'customer_id' => $other->id]);
    }

    public function test_recipients_require_activation_valid_dates_valid_phones_and_unique_ids(): void
    {
        $user = $this->member();
        $valid = $this->customer($user);
        $this->actingAs($user)->put(route('birthday-greetings.update'), ['customer_ids' => [$valid->id]])->assertForbidden();
        $this->post(route('birthday-greetings.activate'));
        $noDate = $this->customer($user, ['date_of_birth' => null]);
        $noPhone = $this->customer($user, ['phone_number' => '123']);
        foreach ([[$noDate->id], [$noPhone->id], [999999], [$valid->id, $valid->id]] as $ids) {
            $this->put(route('birthday-greetings.update'), ['customer_ids' => $ids])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('birthday_greeting_recipients', 0);
    }

    public function test_selections_survive_deactivation_and_can_be_cleared_via_empty_html_form(): void
    {
        $user = $this->member();
        $customer = $this->customer($user);
        $this->optIn($user, $customer);
        $this->actingAs($user)->post(route('birthday-greetings.deactivate'))->assertRedirect();
        $this->assertDatabaseHas('birthday_greeting_settings', ['user_id' => $user->id, 'enabled' => false]);
        $this->assertDatabaseCount('birthday_greeting_recipients', 1);
        $this->post(route('birthday-greetings.activate'));
        $this->put(route('birthday-greetings.update'), ['customer_ids' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('birthday_greeting_recipients', 0);
    }

    public function test_menu_is_visible_only_for_subscribed_hp_and_hm(): void
    {
        foreach (['Health Planner', 'Health Manager'] as $role) {
            $this->actingAs($this->member($role))->get(route('subscriptions.index'))->assertSee(route('birthday-greetings.index'), false);
        }
        $this->actingAs($this->member(subscribed: false))->get(route('subscriptions.index'))->assertDontSee('Birthday Greetings');
        $this->actingAs($this->member('Sales Manager'))->get(route('subscriptions.index'))->assertDontSee('Birthday Greetings');
    }

    public function test_gateway_receives_exact_personal_message_once_per_customer_per_day(): void
    {
        $this->gateway();
        $user = $this->member('Health Manager');
        $customer = $this->customer($user);
        $this->optIn($user, $customer);
        $this->customer($user, ['full_name' => 'Unselected Customer']);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['from'] === '+6285121069310'
            && $request['to'] === '+6281298765432'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('Idempotency-Key', "birthday-{$customer->id}-2026-10-20")
            && $request['message'] === "Selamat ulang tahun, Customer Rani 🎉\nSemoga sehat, bahagia, dan penuh berkat di usia yang baru.\n\nSalam hangat,\nNadia Putri\nHealth Manager Coway\n+6281234567890\n\nPesan ini dikirim melalui LetsGrowAcademy.");
        $this->assertDatabaseHas('birthday_greeting_deliveries', ['customer_id' => $customer->id, 'status' => 'sent', 'provider_message_id' => 'wa-123']);
    }

    public function test_unconfigured_gateway_does_not_create_fake_delivery_records(): void
    {
        $user = $this->member();
        $this->optIn($user, $this->customer($user));
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('birthday_greeting_deliveries', 0);
    }

    public function test_sender_skips_disabled_expired_inactive_wrong_owner_wrong_date_and_invalid_data(): void
    {
        $this->gateway();
        $other = $this->member();
        foreach (['disabled', 'expired', 'inactive', 'owner', 'birthday', 'phone', 'profile', 'role'] as $case) {
            $user = $this->member();
            $customer = $this->customer($user);
            $this->optIn($user, $customer);
            match ($case) {
                'disabled' => BirthdayGreetingSetting::where('user_id', $user->id)->update(['enabled' => false]),
                'expired' => $user->subscriptions()->update(['ends_at' => now()->subMinute()]),
                'inactive' => DB::table('users')->where('id', $user->id)->update(['status' => 'Inactive']),
                'owner' => $customer->update(['health_planner_id' => $other->id]),
                'birthday' => $customer->update(['date_of_birth' => '1990-10-21']),
                'phone' => $customer->update(['phone_number' => null]),
                'profile' => $user->update(['phone_number' => null]),
                'role' => $user->syncRoles([]),
            };
        }
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('birthday_greeting_deliveries', 0);
    }

    public function test_failed_response_is_retried_with_same_idempotency_key_without_claiming_success(): void
    {
        config(['birthday-greetings.gateway_url' => 'https://gateway.test/messages', 'birthday-greetings.gateway_token' => 'test-token']);
        $user = $this->member();
        $customer = $this->customer($user);
        $this->optIn($user, $customer);
        Http::fake(['gateway.test/*' => Http::sequence()->push(['success' => false], 200)->push(['success' => true, 'message_id' => 'retry-ok'])]);
        $this->artisan('birthday-greetings:send')->assertFailed();
        $this->assertDatabaseHas('birthday_greeting_deliveries', ['status' => 'failed', 'sent_at' => null]);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(2);
        foreach (Http::recorded() as [$request]) {
            $this->assertTrue($request->hasHeader('Idempotency-Key', "birthday-{$customer->id}-2026-10-20"));
        }
        $this->assertDatabaseHas('birthday_greeting_deliveries', ['status' => 'sent', 'provider_message_id' => 'retry-ok']);
    }

    public function test_processing_claim_is_not_sent_concurrently_but_can_recover_after_timeout(): void
    {
        $this->gateway();
        $user = $this->member();
        $customer = $this->customer($user);
        $this->optIn($user, $customer);
        DB::table('birthday_greeting_deliveries')->insert([
            'user_id' => $user->id, 'customer_id' => $customer->id, 'greeting_date' => '2026-10-20',
            'status' => 'processing', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertNothingSent();
        $this->travel(31)->minutes();
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_leap_day_rule_and_local_start_time(): void
    {
        $this->travelTo(now()->setDate(2027, 2, 28)->setTime(8, 59));
        $this->gateway();
        $user = $this->member();
        $customer = $this->customer($user, ['date_of_birth' => '2000-02-29']);
        $this->optIn($user, $customer);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertNothingSent();
        $this->travel(1)->minutes();
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame('2027-02-28', app(BirthdayGreetings::class)->nextBirthday($customer, \Carbon\CarbonImmutable::now())->toDateString());
    }

    public function test_transferring_customer_does_not_send_a_second_greeting_the_same_day(): void
    {
        $this->gateway();
        $old = $this->member();
        $new = $this->member('Health Manager');
        $customer = $this->customer($old);
        $this->optIn($old, $customer);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        $customer->update(['health_planner_id' => $new->id]);
        $this->optIn($new, $customer);
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_ownership_is_rechecked_when_it_changes_during_a_delivery_batch(): void
    {
        config(['birthday-greetings.gateway_url' => 'https://gateway.test/messages', 'birthday-greetings.gateway_token' => 'test-token']);
        $user = $this->member();
        $other = $this->member('Health Manager');
        $first = $this->customer($user);
        $second = $this->customer($user, ['full_name' => 'Transferred during batch']);
        $this->optIn($user, $first);
        $this->optIn($user, $second);
        Http::fake(function () use ($second, $other) {
            $second->update(['health_planner_id' => $other->id]);

            return Http::response(['success' => true, 'message_id' => 'first-message']);
        });
        $this->artisan('birthday-greetings:send')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertDatabaseMissing('birthday_greeting_deliveries', ['customer_id' => $second->id]);
    }
}
