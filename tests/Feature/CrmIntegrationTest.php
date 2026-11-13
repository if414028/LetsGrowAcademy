<?php

namespace Tests\Feature;

use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\CrmWebhookOutbox;
use App\Services\MidtransService;
use App\Services\RecruitmentEventCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-api-token-with-at-least-32-characters';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'crm.api_token' => self::TOKEN,
            'crm.require_https' => true,
            'crm.webhooks_enabled' => false,
            'crm.registration_webhook_url' => 'https://crm.example.test/registrations',
            'crm.payment_webhook_url' => 'https://crm.example.test/payments',
            'crm.webhook_secret' => 'test-webhook-secret-with-at-least-32-characters',
        ]);
        Http::preventStrayRequests();
    }

    public function test_submission_records_pending_registration_and_separate_whatsapp_consent_without_network_io(): void
    {
        Http::fake();
        $owner = $this->subscriber();
        $url = route('subscriber-recruitment.submit', $owner->landing_page_slug);
        $this->post($url, $this->form(['whatsapp_consent' => '1']))->assertStatus(303);

        $lead = RecruitmentLead::sole();
        $event = CrmWebhookDelivery::sole();
        $this->assertSame('registration.created', $event->event_type);
        $this->assertSame('LGA-'.$lead->id, $event->payload['data']['registration_id']);
        $this->assertSame('pending', $event->payload['data']['payment_status']);
        $this->assertSame('+6281323456789', $event->payload['data']['whatsapp_number']);
        $this->assertTrue($event->payload['data']['whatsapp_consent']);
        $this->assertNotNull($event->payload['data']['whatsapp_consented_at']);
        $this->assertSame($event->id, $event->payload['data']['last_event_sequence']);
        $this->assertNull($event->payload['data']['payment']);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
        Http::assertNothingSent();

        $this->post($url, $this->form())->assertStatus(303);
        $withoutConsent = RecruitmentLead::latest('id')->first();
        $this->assertFalse($withoutConsent->whatsapp_consent);
        $this->assertNull($withoutConsent->whatsapp_consented_at);
        $this->post($url, $this->form(['domicile' => '', 'whatsapp_consent' => 'yes']))
            ->assertSessionHasErrors(['domicile', 'whatsapp_consent']);
        $this->assertDatabaseCount('crm_webhook_deliveries', 2);
    }

    public function test_outbox_failure_rolls_back_registration_instead_of_losing_the_event(): void
    {
        $owner = $this->subscriber();
        $this->mock(CrmWebhookOutbox::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Storage failure'));
        $this->withoutExceptionHandling();

        try {
            $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
            $this->fail('Expected the outbox failure to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storage failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('recruitment_leads', 0);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    public function test_backup_requires_https_and_server_token_even_for_a_logged_in_member(): void
    {
        $owner = $this->subscriber();
        $path = '/api/v1/crm/registrations';
        $this->actingAs($owner)->getJson('https://localhost'.$path)->assertUnauthorized();
        $this->withToken('incorrect')->getJson('https://localhost'.$path)->assertUnauthorized();
        $this->withToken(self::TOKEN)->getJson('http://localhost'.$path)->assertForbidden();
        $this->getJson('https://localhost'.$path)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        config(['crm.api_token' => '']);
        $this->getJson('https://localhost'.$path)->assertStatus(503);
    }

    public function test_backup_paginates_pending_and_historical_registrations_without_exposing_checkout_credentials(): void
    {
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $first = RecruitmentLead::sole();
        $legacy = $this->legacyLead($owner);
        $this->legacyLead($owner, ['source' => 'another_form']);

        $url = 'https://localhost/api/v1/crm/registrations';
        $response = $this->withToken(self::TOKEN)->getJson($url.'?per_page=1')->assertOk()
            ->assertJsonPath('data.0.registration_id', 'LGA-'.$first->id)
            ->assertJsonPath('data.0.payment_status', 'pending')
            ->assertJsonPath('data.0.whatsapp_consent', false)
            ->assertJsonPath('meta.has_more', true);
        $this->assertStringNotContainsString($first->event_checkout_id, $response->getContent());
        $cursor = $response->json('meta.next_cursor');
        $this->getJson($url.'?per_page=1&cursor='.urlencode($cursor))->assertOk()
            ->assertJsonPath('data.0.registration_id', 'LGA-'.$legacy->id)
            ->assertJsonPath('data.0.whatsapp_consent', null)
            ->assertJsonPath('meta.has_more', false);
        $this->getJson($url.'/LGA-'.$first->id)->assertOk()->assertJsonPath('data.source.owner_user_id', $owner->id);
        $this->getJson($url.'/LGA-9999999')->assertNotFound();
        $this->getJson($url.'/LGA-'.($legacy->id + 1))->assertNotFound();
    }

    public function test_backup_incremental_filter_uses_offset_and_includes_payment_only_changes(): void
    {
        $owner = $this->subscriber();
        $this->freezeTime();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $lead = RecruitmentLead::sole();
        $watermark = now()->addSecond()->utc()->format('Y-m-d\TH:i:sP');
        $this->travel(2)->seconds();
        $payment = $this->payment($lead);
        app(RecruitmentEventCheckout::class)->update($payment, $this->gatewayStatus($payment, 'pending'));

        $this->withToken(self::TOKEN)->getJson('https://localhost/api/v1/crm/registrations?updated_since='.urlencode($watermark))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.payment.transaction_id', 'transaction-'.$payment->id);
    }

    public function test_malformed_filters_and_cursors_return_json_validation_errors(): void
    {
        $url = 'https://localhost/api/v1/crm/registrations';
        $this->withToken(self::TOKEN);
        foreach (['invalid', base64_encode('null'), base64_encode('[]'), base64_encode('{"id":1}'),
            base64_encode('{"id":[],"_pointsToNextItems":true}')] as $cursor) {
            $this->getJson($url.'?cursor='.urlencode($cursor))->assertUnprocessable();
        }
        $this->getJson($url.'?per_page=101&updated_since=invalid')->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page', 'updated_since']);
    }

    public function test_verified_payment_records_transaction_and_gateway_payment_time_once(): void
    {
        $this->freezeTime();
        $lead = $this->legacyLead($this->subscriber(), ['event_payment_status' => 'awaiting_payment']);
        $payment = $this->payment($lead);
        $service = app(RecruitmentEventCheckout::class);
        $status = $this->gatewayStatus($payment, 'settlement');
        $status->settlement_time = now()->subMinutes(5)->format('Y-m-d H:i:s');
        $service->update($payment, $status);
        $event = CrmWebhookDelivery::sole();
        $this->assertSame('paid', $event->payload['data']['payment_status']);
        $this->assertSame('transaction-'.$payment->id, $event->payload['transaction']['transaction_id']);
        $this->assertSame(88000, $event->payload['transaction']['class_amount']);
        $this->assertSame(93000, $event->payload['transaction']['total_amount']);
        $this->assertSame(now()->subMinutes(5)->startOfSecond()->utc()->toISOString(), $event->payload['data']['paid_at']);
        $this->assertSame($event->payload['data']['paid_at'], $event->payload['transaction']['paid_at']);

        $this->travel(1)->minutes();
        $service->update($payment, $status);
        $service->update($payment, $this->gatewayStatus($payment, 'pending'));
        $this->assertDatabaseCount('crm_webhook_deliveries', 1);
        $this->assertSame('paid', $lead->fresh()->event_payment_status);
        $service->update($payment, $this->gatewayStatus($payment, 'refund'));
        $this->assertSame('refunded', $lead->fresh()->event_payment_status);
        $this->assertDatabaseCount('crm_webhook_deliveries', 2);
        $this->withToken(self::TOKEN)->getJson('https://localhost/api/v1/crm/registrations/LGA-'.$lead->id)
            ->assertOk()->assertJsonPath('data.payment_status', 'refunded')
            ->assertJsonPath('data.last_event_sequence', CrmWebhookDelivery::max('id'));
    }

    public function test_expiry_retry_and_old_attempt_failures_keep_the_latest_registration_state(): void
    {
        $lead = $this->legacyLead($this->subscriber(), [
            'event_payment_status' => 'awaiting_payment', 'event_checkout_id' => (string) Str::uuid(),
        ]);
        $payment = $this->payment($lead);
        $service = app(RecruitmentEventCheckout::class);
        $service->update($payment, $this->gatewayStatus($payment, 'expire'));
        $this->assertSame('expired', $lead->fresh()->event_payment_status);
        $this->assertSame('expired', CrmWebhookDelivery::sole()->payload['transaction']['payment_status']);

        config(['midtrans.server_key' => 'test']);
        $this->mock(MidtransService::class)->shouldReceive('createSnapRedirectUrl')->once()
            ->andReturn('https://gateway.example.test/checkout');
        $service = app(RecruitmentEventCheckout::class);
        $retry = $service->start($lead);
        $this->assertSame('awaiting_payment', $lead->fresh()->event_payment_status);
        $this->assertSame('pending', CrmWebhookDelivery::latest('id')->first()->payload['data']['payment_status']);
        $service->update($retry, $this->gatewayStatus($retry, 'deny'));
        $this->assertSame('failed', $lead->fresh()->event_payment_status);
        $service->update($retry, $this->gatewayStatus($retry, 'settlement'));
        $this->assertSame('paid', $lead->fresh()->event_payment_status);
        $service->update($payment, $this->gatewayStatus($payment, 'cancel'));
        $this->assertSame('paid', $lead->fresh()->event_payment_status);
        $this->assertSame('paid', CrmWebhookDelivery::latest('id')->first()->payload['data']['payment_status']);
    }

    public function test_delivery_retries_same_event_and_body_with_fresh_hmac_then_stops_after_acknowledgement(): void
    {
        $this->freezeTime();
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $event = CrmWebhookDelivery::sole();
        $originalPayload = $event->payload;
        $eventId = $event->event_id;
        RecruitmentLead::sole()->update(['full_name' => 'Updated after snapshot']);
        config(['crm.webhooks_enabled' => true]);
        Http::fake(['https://crm.example.test/registrations' => Http::sequence()->push('', 503)->push('', 204)]);
        $this->artisan('crm:deliver-webhooks')->assertFailed();
        $this->assertSame(1, $event->fresh()->attempts);
        $this->assertSame('http_503', $event->fresh()->last_error_code);
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        Http::assertSentCount(1);
        $this->travel(61)->seconds();
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        $this->assertNotNull($event->fresh()->delivered_at);
        $this->assertSame(2, $event->fresh()->attempts);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) use ($eventId, $originalPayload) {
            $timestamp = $request->header('X-LGA-Timestamp')[0];
            $expected = 'v1='.hash_hmac('sha256', $timestamp.'.'.$request->body(), config('crm.webhook_secret'));

            return $request->hasHeader('X-LGA-Signature', $expected)
                && $request->hasHeader('X-LGA-Event-Id', $eventId)
                && json_decode($request->body(), true) === $originalPayload;
        });
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_disabled_or_insecure_configuration_never_sends_and_expired_worker_leases_are_recovered(): void
    {
        Http::fake(['*' => Http::response('', 200)]);
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $event = CrmWebhookDelivery::sole();
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        config(['crm.webhooks_enabled' => true, 'crm.registration_webhook_url' => 'http://crm.example.test/registrations']);
        $this->artisan('crm:deliver-webhooks')->assertFailed();
        Http::assertNothingSent();

        config(['crm.registration_webhook_url' => 'https://crm.example.test/registrations']);
        $event->update(['lease_token' => 'locked', 'locked_until' => now()->addMinute()]);
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        Http::assertNothingSent();
        $this->travel(61)->seconds();
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertNotNull($event->fresh()->delivered_at);
        $this->assertNull($event->fresh()->lease_token);
    }

    public function test_connection_failure_and_redirect_are_retried_without_following_the_redirect(): void
    {
        $lead = $this->legacyLead($this->subscriber());
        $payment = $this->payment($lead);
        app(RecruitmentEventCheckout::class)->update($payment, $this->gatewayStatus($payment, 'pending'));
        config(['crm.webhooks_enabled' => true]);
        Http::fake(['https://crm.example.test/payments' => Http::sequence()
            ->pushFailedConnection()->push('', 302, ['Location' => 'https://other.example.test'])]);
        $this->artisan('crm:deliver-webhooks')->assertFailed();
        $this->assertSame('delivery_exception', CrmWebhookDelivery::sole()->last_error_code);
        $this->travel(61)->seconds();
        $this->artisan('crm:deliver-webhooks')->assertFailed();
        $this->assertSame(302, CrmWebhookDelivery::sole()->last_http_status);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://crm.example.test/payments');
    }

    private function subscriber(): User
    {
        $owner = User::factory()->create(['status' => 'Active', 'phone_number' => '081234567890']);
        $owner->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));
        $owner->subscriptions()->create([
            'duration_months' => 3, 'amount' => 150000, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        return $owner;
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Calon Peserta', 'phone_number' => '081323456789', 'domicile' => 'Bandung',
            'activity' => 'Karyawan', 'consent' => '1',
        ], $overrides);
    }

    private function legacyLead(User $owner, array $overrides = []): RecruitmentLead
    {
        return $owner->recruitmentLeads()->create(array_merge([
            'source' => RecruitmentLead::SOURCE, 'source_slug' => $owner->landing_page_slug,
            'source_url' => route('subscriber-recruitment.show', $owner->landing_page_slug),
            'full_name' => 'Peserta Lama', 'phone_number' => '6281323456789',
            'domicile' => 'Jakarta', 'activity' => 'Karyawan', 'consented_at' => now(),
        ], $overrides));
    }

    private function payment(RecruitmentLead $lead): RecruitmentEventPayment
    {
        return $lead->eventPayments()->create([
            'midtrans_order_id' => 'REVT-test-'.$lead->id, 'checkout_url' => 'https://gateway.example.test/checkout',
            'class_amount' => 88000, 'service_fee' => 5000,
        ]);
    }

    private function gatewayStatus(RecruitmentEventPayment $payment, string $status): object
    {
        return (object) [
            'order_id' => $payment->midtrans_order_id, 'gross_amount' => '93000.00', 'status_code' => '200',
            'transaction_id' => 'transaction-'.$payment->id, 'transaction_status' => $status,
        ];
    }
}
