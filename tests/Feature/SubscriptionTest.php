<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\SubscriptionPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
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
                ->withArgs(fn (array $payload) => $payload['transaction_details']['gross_amount'] === 304440
                    && $payload['enabled_payments'] === ['bni_va']
                    && array_column($payload['item_details'], 'price') === [300000, 4000, 440])
                ->andReturn('sandbox-snap-token');
        });

        $user = $this->eligibleUser();

        $response = $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 6,
            'payment_method' => 'bni_va',
        ]);

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('snap_token', 'sandbox-snap-token');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'duration_months' => 6,
            'amount' => 304440,
            'selected_payment_method' => 'bni_va',
            'status' => 'pending',
            'payment_status' => 'pending',
            'snap_token' => 'sandbox-snap-token',
        ]);
    }

    public function test_checkout_uses_server_prices_and_saves_the_original_breakdown(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');
        config()->set('subscription.enabled_payment_methods', ['shopeepay', 'bni_va']);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapToken')->once()
                ->withArgs(fn (array $payload) => $payload['transaction_details']['gross_amount'] === 313940
                    && $payload['enabled_payments'] === ['shopeepay']
                    && array_column($payload['item_details'], 'price') === [300000, 12558, 1382])
                ->andReturn('wallet-token');
        });

        $user = $this->eligibleUser();
        $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 6, 'payment_method' => 'shopeepay',
            'amount' => 1, 'transaction_fee' => 0, 'gateway_vat_amount' => 0,
        ])->assertSessionHas('snap_token', 'wallet-token');

        $subscription = $user->subscriptions()->sole();
        $this->assertSame(12558, $subscription->pricing_details['transaction_fee']);
        $this->assertSame(1382, $subscription->pricing_details['gateway_vat_amount']);

        // A pending checkout keeps its agreed amount, even after rates change.
        config()->set('subscription.monthly_price', 100000);
        config()->set('subscription.gateway_vat_percent', 12);
        $this->actingAs($user)->get(route('subscriptions.index'))
            ->assertOk()->assertSee('Rp 313.940')->assertSee('Rp 12.558')->assertSee('Rp 1.382')
            ->assertSee('PPN 11% biaya transaksi')->assertSee('ShopeePay')
            ->assertSee('"deeplink"', false);

        $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 3, 'payment_method' => 'bni_va',
        ])->assertSessionHas('snap_token', 'wallet-token');
        $this->assertSame(1, $user->subscriptions()->count());
        $this->assertEquals(313940, $subscription->fresh()->amount);
    }

    public function test_qris_checkout_adds_no_gateway_fee_or_vat(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapToken')->once()
                ->withArgs(fn (array $payload) => $payload['transaction_details']['gross_amount'] === 150000
                    && $payload['enabled_payments'] === ['gopay']
                    && count($payload['item_details']) === 1)
                ->andReturn('qris-token');
        });

        $user = $this->eligibleUser();
        $this->actingAs($user)->post(route('subscriptions.store'), [
            'duration_months' => 3, 'payment_method' => 'gopay_qris',
        ])->assertSessionHas('snap_token', 'qris-token');

        $pricing = $user->subscriptions()->sole()->pricing_details;
        $this->assertTrue($pricing['merchant_covers_fee']);
        $this->assertSame(0, $pricing['transaction_fee']);
        $this->assertSame(0, $pricing['gateway_vat_amount']);
        $this->assertSame(1050, $pricing['estimated_gateway_fee']);
        $this->assertSame(116, $pricing['estimated_gateway_vat_amount']);
        $this->assertSame('qr', $pricing['snap_ui_mode']);
        $this->actingAs($user)->get(route('subscriptions.index'))->assertOk()->assertSee('"qr"', false);
    }

    public function test_only_configured_methods_are_visible_and_accepted(): void
    {
        config()->set('subscription.enabled_payment_methods', ['dana']);
        $user = $this->eligibleUser();
        $this->mock(MidtransService::class)->shouldNotReceive('createSnapToken');

        $this->actingAs($user)->get(route('subscriptions.index'))
            ->assertOk()->assertSee('value="dana"', false)
            ->assertDontSee('value="bni_va"', false)
            ->assertDontSee('value="shopeepay"', false);

        foreach (['bni_va', 'credit_card', 'invalid', null, ['dana']] as $method) {
            $this->actingAs($user)->from(route('subscriptions.index'))->post(route('subscriptions.store'), [
                'duration_months' => 6, 'payment_method' => $method,
            ])->assertSessionHasErrors('payment_method');
        }

        // Invalid old input must also render safely on the redirected page.
        $this->get(route('subscriptions.index'))->assertOk();
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_empty_method_configuration_disables_checkout(): void
    {
        config()->set('subscription.enabled_payment_methods', []);
        $this->actingAs($this->eligibleUser())->get(route('subscriptions.index'))
            ->assertOk()->assertSee('Metode pembayaran belum tersedia.');
    }

    public function test_checkout_defaults_match_the_confirmed_active_methods(): void
    {
        $this->actingAs($this->eligibleUser())->get(route('subscriptions.index'))
            ->assertOk()->assertViewHas('paymentMethods', function (array $methods): bool {
                $this->assertEqualsCanonicalizing([
                    'bni_va', 'cimb_va', 'permata_va', 'bri_va', 'gopay_qris', 'bsi_va', 'gopay', 'echannel',
                ], array_keys($methods));

                return true;
            });
    }

    public function test_admin_sees_estimated_qris_expense_separately_from_customer_total(): void
    {
        $user = $this->eligibleUser();
        $pricing = app(SubscriptionPricing::class)->quote(6, 'gopay_qris');
        Subscription::create([
            'user_id' => $user->id, 'duration_months' => 6, 'amount' => $pricing['total'],
            'selected_payment_method' => 'gopay_qris', 'pricing_details' => $pricing,
            'midtrans_order_id' => 'SUB-QRIS-ADMIN', 'status' => 'pending', 'submitted_at' => now(),
        ]);
        $admin = User::factory()->create(['status' => 'Active']);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Admin']));

        $this->actingAs($admin)->get(route('admin.subscriptions.index'))
            ->assertOk()->assertSee('GoPay Dynamic QRIS')->assertSee('Rp 300.000')
            ->assertSee('Estimasi biaya gateway merchant: Rp 2.100 + PPN Rp 231.');
    }

    public function test_legacy_pending_checkout_keeps_its_original_total_without_a_pricing_snapshot(): void
    {
        $user = $this->eligibleUser();
        Subscription::create([
            'user_id' => $user->id, 'duration_months' => 6, 'amount' => 300000,
            'midtrans_order_id' => 'SUB-LEGACY', 'snap_token' => 'old-token',
            'status' => 'pending', 'submitted_at' => now(),
        ]);

        $this->actingAs($user)->get(route('subscriptions.index'))
            ->assertOk()->assertSee('Rp 300.000')->assertSee('old-token')
            ->assertSee('Lanjutkan Pembayaran')->assertDontSee('PPN 11% biaya transaksi');
    }

    public function test_failed_gateway_checkout_does_not_leave_a_pending_subscription(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');
        $this->mock(MidtransService::class)->shouldReceive('createSnapToken')->once()
            ->andThrow(new \RuntimeException('Gateway temporarily unavailable'));

        $this->actingAs($this->eligibleUser())->post(route('subscriptions.store'), [
            'duration_months' => 6, 'payment_method' => 'bni_va',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_payment_sync_checks_total_including_fees(): void
    {
        $user = $this->eligibleUser();
        $subscription = Subscription::create([
            'user_id' => $user->id, 'duration_months' => 6, 'amount' => 304440,
            'midtrans_order_id' => 'SUB-WITH-FEES', 'payment_status' => 'pending',
            'status' => 'pending', 'submitted_at' => now(),
        ]);
        $this->mock(MidtransService::class)->shouldReceive('transactionStatus')->twice()
            ->andReturn(
                (object) ['transaction_status' => 'settlement', 'gross_amount' => '300000.00'],
                (object) ['transaction_status' => 'settlement', 'gross_amount' => '304440.00'],
            );

        $this->actingAs($user)->postJson(route('subscriptions.sync-payment', $subscription))->assertStatus(503);
        $this->assertSame('pending', $subscription->fresh()->payment_status);
        $this->postJson(route('subscriptions.sync-payment', $subscription))
            ->assertOk()->assertJson(['payment_status' => 'paid', 'subscription_status' => 'active']);
    }

    public function test_valid_midtrans_notification_activates_subscription_only_once(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');

        $user = $this->eligibleUser();
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
        $user = $this->eligibleUser();
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
        $user = $this->eligibleUser();
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
            ->assertSee('Selling Kit')
            ->assertSee('Galeri Selling Kit')
            ->assertSee('Katalog &amp; Edukasi Produk', false)
            ->assertSee('Panduan Penjualan')
            ->assertSee('Rekrutmen Health Planner')
            ->assertSee(route('selling-kit.category', 'katalog-edukasi-produk'), false)
            ->assertDontSee('Catalog September 2026');

        $this->actingAs($user)
            ->get(route('selling-kit.category', 'katalog-edukasi-produk'))
            ->assertOk()
            ->assertSee('Catalog September 2026')
            ->assertSee('Waspada Abu Vulkanik');

        $this->actingAs($user)
            ->get(route('selling-kit.category', 'panduan-penjualan'))
            ->assertOk()
            ->assertSee('Customer Confirmation')
            ->assertSee('Alur Key In');

        $this->actingAs($user)
            ->get(route('selling-kit.category', 'rekrutmen-health-planner'))
            ->assertOk()
            ->assertSee('Alur Rekrut Calon HP')
            ->assertSee('Brosur LGA September 2026');

        $this->actingAs($user)
            ->get(route('selling-kit.show', 'customer-confirmation'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('selling-kit.download', 'customer-confirmation'))
            ->assertOk()
            ->assertDownload('customer-confirmation.pdf');
    }

    public function test_non_subscriber_cannot_open_selling_kit_document_directly(): void
    {
        $user = User::factory()->create(['status' => 'Active']);

        $this->actingAs($user)
            ->get(route('selling-kit.show', 'customer-confirmation'))
            ->assertRedirect(route('subscriptions.index'));

        $this->actingAs($user)
            ->get(route('selling-kit.category', 'panduan-penjualan'))
            ->assertRedirect(route('subscriptions.index'));
    }

    private function eligibleUser(): User
    {
        $user = User::factory()->create(['status' => 'Active']);
        $user->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));

        return $user;
    }
}
