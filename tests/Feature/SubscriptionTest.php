<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_midtrans_subscription_checkout(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapToken')
                ->once()
                ->withArgs(fn (array $payload) => $payload['transaction_details']['gross_amount'] === 300000)
                ->andReturn('sandbox-snap-token');
        });

        $user = User::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 6,
        ]);

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('snap_token', 'sandbox-snap-token');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'duration_months' => 6,
            'amount' => 300000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'snap_token' => 'sandbox-snap-token',
        ]);
    }

    public function test_valid_midtrans_notification_activates_subscription_only_once(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');

        $user = User::factory()->create(['status' => 'Active']);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 3,
            'amount' => 150000,
            'midtrans_order_id' => 'SUB-TEST-1',
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $verifiedStatus = (object) [
            'order_id' => 'SUB-TEST-1',
            'transaction_id' => 'transaction-1',
            'transaction_status' => 'settlement',
            'gross_amount' => '150000.00',
            'payment_type' => 'bank_transfer',
        ];

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($verifiedStatus): void {
            $mock->shouldReceive('transactionStatus')->twice()->andReturn($verifiedStatus);
        });

        $payload = [
            'order_id' => 'SUB-TEST-1',
            'status_code' => '200',
            'gross_amount' => '150000.00',
        ];
        $payload['signature_key'] = hash('sha512', implode('', $payload).'SB-Mid-server-test');

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $firstEndsAt = $subscription->fresh()->ends_at;

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();

        $subscription->refresh();
        $this->assertSame('paid', $subscription->payment_status);
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->paid_at);
        $this->assertTrue($firstEndsAt->equalTo($subscription->ends_at));
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');

        $this->postJson(route('payments.midtrans.notification'), [
            'order_id' => 'SUB-TEST-1',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => 'invalid',
        ])->assertForbidden();
    }

    public function test_midtrans_dashboard_notification_check_is_accepted(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');

        $payload = [
            'order_id' => 'payment_notif_test_M123_example',
            'status_code' => '200',
            'gross_amount' => '10000.00',
        ];
        $payload['signature_key'] = hash('sha512', implode('', $payload).'SB-Mid-server-test');

        $this->postJson(route('payments.midtrans.notification'), $payload)
            ->assertOk()
            ->assertJson(['message' => 'Midtrans notification endpoint is ready.']);
    }

    public function test_user_can_sync_cancelled_payment_and_start_over(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 6,
            'amount' => 300000,
            'midtrans_order_id' => 'SUB-CANCELLED-1',
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('transactionStatus')->once()->andReturn((object) [
                'order_id' => 'SUB-CANCELLED-1',
                'transaction_id' => 'transaction-cancelled-1',
                'transaction_status' => 'cancel',
                'gross_amount' => '300000.00',
                'payment_type' => 'qris',
            ]);
        });

        $this->actingAs($user)
            ->postJson(route('subscriptions.sync-payment', $subscription))
            ->assertOk()
            ->assertJson([
                'payment_status' => 'cancelled',
                'subscription_status' => 'rejected',
            ]);

        $this->assertNull($user->pendingSubscription());
    }

    public function test_finish_redirect_syncs_successful_payment(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 6,
            'amount' => 300000,
            'midtrans_order_id' => 'SUB-FINISH-1',
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('transactionStatus')->once()->andReturn((object) [
                'order_id' => 'SUB-FINISH-1',
                'transaction_id' => 'transaction-finish-1',
                'transaction_status' => 'settlement',
                'gross_amount' => '300000.00',
                'payment_type' => 'qris',
            ]);
        });

        $this->actingAs($user)
            ->get(route('subscriptions.payment-finish', [
                'order_id' => 'SUB-FINISH-1',
                'transaction_status' => 'settlement',
            ]))
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('payment_success', true);

        $this->assertSame('paid', $subscription->fresh()->payment_status);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_non_subscriber_cannot_access_selling_kit(): void
    {
        $user = User::factory()->create(['status' => 'Active']);

        $this->actingAs($user)
            ->get(route('selling-kit.index'))
            ->assertRedirect(route('subscriptions.index'));
    }

    public function test_active_subscriber_can_access_selling_kit(): void
    {
        $user = User::factory()->create(['status' => 'Active']);

        Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 3,
            'amount' => 150000,
            'status' => 'active',
            'submitted_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(3),
        ]);

        $this->actingAs($user)
            ->get(route('selling-kit.index'))
            ->assertOk()
            ->assertSee('Selling Kit');
    }
}
