<?php

namespace Tests\Feature;

use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\RecruitmentEventCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RecruitmentEventPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'midtrans.server_key' => 'server-test',
            'midtrans.client_key' => 'client-test',
            'subscription.enabled_payment_methods' => ['bni_va', 'gopay_qris', 'gopay'],
            'subscription.gateway_vat_percent' => 11,
            'crm.api_token' => str_repeat('test-token-', 4),
        ]);
    }

    public function test_event_quotes_follow_bank_wallet_and_merchant_funded_qris_rates(): void
    {
        $lead = $this->lead();
        $this->get($this->url($lead))->assertOk()
            ->assertSee('Biaya tambahan')->assertSee('Metode pembayaran')
            ->assertDontSee('Biaya layanan aplikasi')->assertDontSee('ditanggung')->assertDontSee('Midtrans')
            ->assertViewHas('checkoutConfig', function ($config) {
                $this->assertSame(92440, $config['quotes']['bni_va']['total']);
                $this->assertSame(4000, $config['quotes']['bni_va']['transaction_fee']);
                $this->assertSame(440, $config['quotes']['bni_va']['gateway_vat_amount']);
                $this->assertSame(92090, $config['quotes']['gopay']['total']);
                $this->assertSame(3684, $config['quotes']['gopay']['transaction_fee']);
                $this->assertSame(406, $config['quotes']['gopay']['gateway_vat_amount']);
                $this->assertSame(88000, $config['quotes']['gopay_qris']['total']);
                $this->assertSame(0, $config['quotes']['gopay_qris']['gateway_vat_amount']);
                $this->assertTrue($config['quotes']['gopay_qris']['merchant_covers_fee']);

                return true;
            });
        $this->assertDatabaseCount('recruitment_event_payments', 0);
    }

    public function test_checkout_ignores_browser_amounts_saves_the_snapshot_and_reports_the_breakdown_to_crm(): void
    {
        $lead = $this->lead();
        $this->mock(MidtransService::class)->shouldReceive('createSnapTransaction')->once()
            ->with(Mockery::on(fn ($payload) => $payload['transaction_details']['gross_amount'] === 92440
                && $payload['enabled_payments'] === ['bni_va']
                && array_column($payload['item_details'], 'price') === [88000, 4000, 440]))
            ->andReturn($this->snap());
        $this->post($this->url($lead, 'pay'), [
            'payment_method' => 'bni_va', 'gross_amount' => 1, 'class_amount' => 1,
            'service_fee' => 0, 'transaction_fee' => 0, 'gateway_vat_amount' => 0,
        ])->assertStatus(303)->assertRedirect($this->url($lead));
        $payment = RecruitmentEventPayment::sole();
        $this->assertSame(92440, $payment->total());
        $this->assertSame(4440, (int) $payment->service_fee);
        $this->assertSame('bni_va', $payment->selected_payment_method);
        $this->assertSame(440, $payment->pricing_details['gateway_vat_amount']);
        $event = CrmWebhookDelivery::sole();
        $this->assertSame(92440, $event->payload['transaction']['total_amount']);
        $this->assertSame(4440, $event->payload['transaction']['service_fee']);
        $this->assertSame(4000, $event->payload['transaction']['transaction_fee']);
        $this->assertSame(440, $event->payload['transaction']['gateway_vat_amount']);
        $this->withToken(config('crm.api_token'))->getJson('https://localhost/api/v1/crm/registrations/'.$lead->registrationId())
            ->assertOk()->assertJsonPath('data.payment.total_amount', 92440)
            ->assertJsonPath('data.payment.selected_payment_method', 'bni_va')
            ->assertJsonPath('data.payment.gateway_vat_amount', 440)
            ->assertDontSee('private-snap-token')->assertDontSee('gateway.example');
    }

    public function test_pending_payment_keeps_its_original_method_amount_and_ui_mode_when_rates_change(): void
    {
        $lead = $this->lead();
        $this->mock(MidtransService::class)->shouldReceive('createSnapTransaction')->once()->andReturn($this->snap());
        $this->post($this->url($lead, 'pay'), ['payment_method' => 'gopay_qris'])->assertRedirect();
        $payment = RecruitmentEventPayment::sole();
        config(['subscription.enabled_payment_methods' => ['bni_va'], 'subscription.gateway_vat_percent' => 20]);
        $this->post($this->url($lead, 'pay'), ['payment_method' => 'bni_va'])->assertRedirect($this->url($lead));
        $this->get($this->url($lead))->assertOk()
            ->assertViewHas('locked', true)
            ->assertViewHas('checkoutConfig', fn ($config) => $config['total'] === 88000 && $config['snapUiMode'] === 'qr')
            ->assertSee('GoPay Dynamic QRIS')->assertDontSee('<select', false);
        $this->assertSame('gopay_qris', $payment->fresh()->selected_payment_method);
        $this->assertSame(88000, $payment->fresh()->total());
        $this->assertDatabaseCount('recruitment_event_payments', 1);
        $this->assertDatabaseCount('crm_webhook_deliveries', 1);
    }

    public function test_legacy_pending_checkout_resumes_its_existing_redirect_and_total_without_inventing_tax(): void
    {
        $lead = $this->lead();
        $payment = $this->legacy($lead);
        config(['subscription.enabled_payment_methods' => [], 'midtrans.server_key' => null, 'midtrans.client_key' => null]);
        $this->get($this->url($lead))->assertOk()->assertSee('Rp93.000')->assertSee('Biaya tambahan')
            ->assertDontSee('Biaya transaksi</dt>', false)->assertViewHas('quote', null);
        $this->post($this->url($lead, 'pay'))->assertRedirect($payment->checkout_url);
        $this->assertDatabaseCount('recruitment_event_payments', 1);
        $data = app(\App\Services\CrmRegistrationData::class)->payment($payment);
        $this->assertNull($data['transaction_fee']);
        $this->assertNull($data['gateway_vat_amount']);
        $this->assertSame(93000, $data['total_amount']);
    }

    public function test_expired_payment_can_retry_with_a_new_method_without_changing_the_old_invoice(): void
    {
        $lead = $this->lead();
        $previous = $this->legacy($lead, 'expired');
        config(['recruitment.event_payment.class_amount' => 99000]);
        $this->mock(MidtransService::class)->shouldReceive('createSnapTransaction')->once()
            ->with(Mockery::on(fn ($payload) => $payload['transaction_details']['gross_amount'] === 88000
                && $payload['enabled_payments'] === ['gopay'] && count($payload['item_details']) === 1))
            ->andReturn($this->snap());
        $this->post($this->url($lead, 'pay'), ['payment_method' => 'gopay_qris'])->assertRedirect();
        $latest = $lead->eventPayments()->latest('id')->first();
        $this->assertSame(88000, $latest->total());
        $this->assertSame(93000, $previous->fresh()->total());
        $this->assertNotSame($previous->midtrans_order_id, $latest->midtrans_order_id);
        app(RecruitmentEventCheckout::class)->update($latest, (object) [
            'order_id' => $latest->midtrans_order_id, 'gross_amount' => '88000.00',
            'status_code' => '200', 'transaction_status' => 'settlement', 'payment_type' => 'qris',
        ]);
        $this->assertSame('paid', $lead->fresh()->event_payment_status);
        $this->post($this->url($lead, 'pay'), ['payment_method' => 'bni_va'])->assertRedirect();
        $this->assertDatabaseCount('recruitment_event_payments', 2);
    }

    public function test_missing_disabled_or_malformed_methods_never_create_a_transaction(): void
    {
        $lead = $this->lead();
        $this->mock(MidtransService::class)->shouldNotReceive('createSnapTransaction');
        foreach ([null, 'other_qris', 'seabank_va', ['bni_va']] as $method) {
            $this->postJson($this->url($lead, 'pay'), ['payment_method' => $method])->assertUnprocessable()
                ->assertJsonValidationErrors('payment_method');
        }
        config(['subscription.enabled_payment_methods' => []]);
        $this->get($this->url($lead))->assertOk()->assertSee('Metode pembayaran belum tersedia.');
        $this->postJson($this->url($lead, 'pay'), ['payment_method' => 'bni_va'])->assertUnprocessable();
        $this->assertDatabaseCount('recruitment_event_payments', 0);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    public function test_gateway_failure_does_not_leave_a_payment_or_crm_event(): void
    {
        $lead = $this->lead();
        $this->mock(MidtransService::class)->shouldReceive('createSnapTransaction')->once()->andThrow(new RuntimeException('Test gateway unavailable'));
        $this->post($this->url($lead, 'pay'), ['payment_method' => 'bni_va'])->assertRedirect($this->url($lead))->assertSessionHas('payment_error');
        $this->assertDatabaseCount('recruitment_event_payments', 0);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    private function lead(): RecruitmentLead
    {
        $owner = User::factory()->create();

        return $owner->recruitmentLeads()->create([
            'source' => RecruitmentLead::SOURCE, 'source_slug' => 'test-owner', 'source_url' => 'https://localhost/rekrutmen/test-owner',
            'full_name' => 'Peserta Uji', 'phone_number' => '6281234567890', 'domicile' => 'Jakarta', 'activity' => 'Karyawan',
            'consented_at' => now(), 'event_checkout_id' => (string) Str::uuid(), 'event_payment_status' => 'awaiting_payment',
        ]);
    }

    private function legacy(RecruitmentLead $lead, string $status = 'pending'): RecruitmentEventPayment
    {
        return $lead->eventPayments()->create([
            'midtrans_order_id' => 'REVT-'.Str::uuid(), 'class_amount' => 88000, 'service_fee' => 5000,
            'checkout_url' => 'https://gateway.example/legacy', 'status' => $status,
        ]);
    }

    private function snap(): object
    {
        return (object) ['token' => 'private-snap-token', 'redirect_url' => 'https://gateway.example/new'];
    }

    private function url(RecruitmentLead $lead, string $action = 'show'): string
    {
        return route('subscriber-recruitment.payment.'.$action, [$lead->source_slug, $lead->event_checkout_id]);
    }
}
