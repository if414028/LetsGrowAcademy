<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionLandingPageRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_mandiri_payment_renders_subscription_when_user_has_no_slug(): void
    {
        $user = $this->userWithoutSlug();
        $subscription = $this->subscription($user, 'pending');
        $this->mock(MidtransService::class)->shouldReceive('transactionStatus')->once()
            ->with('SUB-MANDIRI-RECOVERY')->andReturn((object) [
                'order_id' => 'SUB-MANDIRI-RECOVERY',
                'transaction_id' => 'mandiri-recovery-transaction',
                'transaction_status' => 'settlement',
                'payment_type' => 'echannel',
                'gross_amount' => '304440.00',
            ]);

        $this->actingAs($user)->followingRedirects()
            ->get(route('subscriptions.payment-finish', ['order_id' => 'SUB-MANDIRI-RECOVERY']))
            ->assertOk()->assertSee('Terima kasih sudah subscribe!')->assertSee('Subscriber aktif')
            ->assertSee(route('subscriber-landing.show', 'lina-mandiri-dst037'), false);

        $this->assertSame('lina-mandiri-dst037', $user->fresh()->landing_page_slug);
        $this->assertSame('paid', $subscription->fresh()->payment_status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertEquals(304440, $subscription->fresh()->amount);
    }

    #[DataProvider('subscriberPages')]
    public function test_existing_paid_subscriber_can_open_pages_directly_without_a_slug(string $route): void
    {
        $user = $this->userWithoutSlug();
        $subscription = $this->subscription($user, 'active');
        $originalEnd = $subscription->ends_at->copy();
        $this->mock(MidtransService::class)->shouldNotReceive('transactionStatus');

        $this->actingAs($user)->get(route($route))->assertOk();
        $this->assertSame('lina-mandiri-dst037', $user->fresh()->landing_page_slug);
        $this->get(route($route))->assertOk();
        $this->assertSame('lina-mandiri-dst037', $user->fresh()->landing_page_slug);
        $this->assertTrue($subscription->fresh()->ends_at->equalTo($originalEnd));
        $this->assertSame('paid', $subscription->fresh()->payment_status);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->get(route('subscriber-landing.show', 'lina-mandiri-dst037'))->assertOk();
    }

    public static function subscriberPages(): array
    {
        return [
            'subscription' => ['subscriptions.index'],
            'product landing page' => ['subscriber-landing.index'],
            'recruitment landing page' => ['subscriber-recruitment.index'],
            'grow ads' => ['grow-ads.index'],
        ];
    }

    public function test_missing_slug_recovery_handles_an_existing_matching_slug(): void
    {
        $user = $this->userWithoutSlug();
        User::factory()->create(['name' => 'Lina Mandiri', 'dst_code' => 'DST037']);
        $this->subscription($user, 'active');

        $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();
        $this->assertSame('lina-mandiri-dst037-2', $user->fresh()->landing_page_slug);
    }

    public function test_recovery_does_not_replace_a_slug_already_created_by_another_request(): void
    {
        $user = $this->userWithoutSlug();
        User::whereKey($user->id)->update(['landing_page_slug' => 'existing-public-link']);

        $this->assertSame('existing-public-link', $user->ensureLandingPageSlug());
        $this->assertSame('existing-public-link', $user->landing_page_slug);
        $this->assertSame('existing-public-link', $user->fresh()->landing_page_slug);
    }

    private function userWithoutSlug(): User
    {
        $user = User::factory()->create([
            'name' => 'Lina Mandiri', 'dst_code' => 'DST037',
            'phone_number' => '081234567890', 'status' => 'Active',
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));
        // Reproduce imported/legacy records without triggering the saving observer.
        User::whereKey($user->id)->update(['landing_page_slug' => null]);

        return $user->refresh();
    }

    private function subscription(User $user, string $status): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id, 'duration_months' => 6, 'amount' => 304440,
            'midtrans_order_id' => 'SUB-MANDIRI-RECOVERY', 'selected_payment_method' => 'echannel',
            'payment_type' => $status === 'active' ? 'echannel' : null,
            'status' => $status, 'payment_status' => $status === 'active' ? 'paid' : 'pending',
            'paid_at' => $status === 'active' ? now() : null,
            'submitted_at' => now(), 'starts_at' => $status === 'active' ? now() : null,
            'ends_at' => $status === 'active' ? now()->addMonthsNoOverflow(6) : null,
        ]);
    }
}
