<?php

namespace Tests\Feature;

use App\Models\CrmRegistrationTombstone;
use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\CrmWebhookOutbox;
use App\Services\RecruitmentRegistrationPrivacy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class CrmRegistrationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'privacy-api-token-with-at-least-32-characters';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'crm.environment' => 'staging', 'crm.api_token' => self::TOKEN, 'crm.require_https' => true,
            'crm.webhooks_enabled' => false,
            'crm.registration_webhook_url' => 'https://crm.example.test/registrations',
            'crm.payment_webhook_url' => 'https://crm.example.test/payments',
            'crm.webhook_secret' => 'privacy-webhook-secret-with-at-least-32-characters',
        ]);
        Http::preventStrayRequests();
    }

    public function test_opt_out_supersedes_pending_opt_in_snapshots_and_is_idempotent(): void
    {
        $lead = $this->lead();
        $original = CrmWebhookDelivery::sole();
        $consentedAt = $lead->whatsapp_consented_at;
        $this->travel(2)->seconds();
        $since = now()->subSecond()->utc()->format('Y-m-d\TH:i:s\Z');
        $this->artisan('crm:revoke-whatsapp-consent', ['registration' => $lead->registrationId()])->assertSuccessful();
        $lead->refresh();
        $this->assertFalse($lead->whatsapp_consent);
        $this->assertNotNull($lead->whatsapp_consent_revoked_at);
        $this->assertTrue($lead->whatsapp_consented_at->equalTo($consentedAt));
        $event = CrmWebhookDelivery::sole();
        $this->assertGreaterThan($original->id, $event->id);
        $this->assertSame('registration.updated', $event->event_type);
        $this->assertFalse($event->payload['data']['whatsapp_consent']);
        $this->assertNotNull($event->payload['data']['whatsapp_consent_revoked_at']);
        $this->withToken(self::TOKEN)->getJson('https://localhost/api/v1/crm/registrations?updated_since='.urlencode($since))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.whatsapp_consent', false);
        $revoked = $lead->whatsapp_consent_revoked_at;
        $this->travel(1)->minutes();
        $this->artisan('crm:revoke-whatsapp-consent', ['registration' => $lead->registrationId()])->assertSuccessful();
        $this->assertDatabaseCount('crm_webhook_deliveries', 1);
        $this->assertTrue($revoked->equalTo($lead->fresh()->whatsapp_consent_revoked_at));
    }

    public function test_deletion_removes_pii_and_payment_rows_but_retains_a_retryable_tombstone(): void
    {
        $lead = $this->lead();
        $registrationId = $lead->registrationId();
        $lead->eventPayments()->create([
            'midtrans_order_id' => 'REVT-'.Str::uuid(), 'class_amount' => 88000, 'service_fee' => 5000,
            'checkout_url' => 'https://gateway.example.test/private-checkout',
        ]);
        $this->artisan('crm:delete-registration', ['registration' => $registrationId, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('recruitment_leads', 0);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
        $event = CrmWebhookDelivery::sole();
        $this->assertNull($event->recruitment_lead_id);
        $this->assertSame('registration.deleted', $event->event_type);
        $this->assertSame($registrationId, $event->payload['data']['registration_id']);
        foreach ([$lead->full_name, $lead->phone_number, $lead->event_checkout_id, 'private-checkout'] as $pii) {
            $this->assertStringNotContainsString($pii, json_encode($event->payload));
        }
        $this->assertSame($event->id, CrmRegistrationTombstone::sole()->last_event_sequence);
        $this->withToken(self::TOKEN)->getJson('https://localhost/api/v1/crm/registrations/'.$registrationId)->assertNotFound();
        $this->getJson('https://localhost/api/v1/crm/registrations')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('https://localhost/api/v1/crm/registrations/deleted')->assertOk()
            ->assertJsonPath('data.0.registration_id', $registrationId)
            ->assertJsonPath('data.0.environment', 'staging')
            ->assertJsonPath('data.0.last_event_sequence', $event->id);
        $this->artisan('crm:delete-registration', ['registration' => $registrationId, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('crm_registration_tombstones', 1);
        $this->assertDatabaseCount('crm_webhook_deliveries', 1);
    }

    public function test_deletion_requires_confirmation_and_crm_tokens_cannot_write(): void
    {
        $lead = $this->lead();
        $this->artisan('crm:delete-registration', ['registration' => $lead->registrationId()])
            ->expectsConfirmation('Permanently delete '.$lead->registrationId().', its payment records, and CRM snapshots?', 'no')
            ->assertSuccessful();
        $this->assertDatabaseCount('recruitment_leads', 1);
        $this->withToken(self::TOKEN)->deleteJson('https://localhost/api/v1/crm/registrations/'.$lead->registrationId())->assertStatus(405);
        $this->postJson('https://localhost/api/v1/crm/registrations/'.$lead->registrationId())->assertStatus(405);
        $this->assertDatabaseCount('crm_registration_tombstones', 0);
    }

    public function test_deletion_backup_requires_auth_and_supports_pagination_and_utc_filters(): void
    {
        $first = $this->lead();
        app(RecruitmentRegistrationPrivacy::class)->delete($first);
        $this->travel(2)->seconds();
        $since = now()->subSecond()->utc()->toISOString();
        $second = $this->lead();
        app(RecruitmentRegistrationPrivacy::class)->delete($second);
        $url = 'https://localhost/api/v1/crm/registrations/deleted';
        $this->getJson($url)->assertUnauthorized();
        $response = $this->withToken(self::TOKEN)->getJson($url.'?per_page=1')->assertOk()
            ->assertJsonPath('data.0.registration_id', $first->registrationId())->assertJsonPath('meta.has_more', true);
        $this->getJson($url.'?per_page=1&cursor='.urlencode($response->json('meta.next_cursor')))->assertOk()
            ->assertJsonPath('data.0.registration_id', $second->registrationId())->assertJsonPath('meta.has_more', false);
        $this->getJson($url.'?updated_since='.urlencode($since))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.registration_id', $second->registrationId());
        $this->getJson($url.'?cursor=invalid')->assertUnprocessable();
    }

    public function test_updated_and_deleted_events_use_the_registration_receiver_and_retry_normally(): void
    {
        $revoked = $this->lead();
        app(RecruitmentRegistrationPrivacy::class)->revokeWhatsapp($revoked);
        $deleted = $this->lead();
        app(RecruitmentRegistrationPrivacy::class)->delete($deleted);
        config(['crm.webhooks_enabled' => true]);
        Http::fake(['https://crm.example.test/registrations' => Http::sequence()->push('', 503)->push('', 204)->push('', 204)]);
        $this->artisan('crm:deliver-webhooks')->assertFailed();
        Http::assertSentCount(2);
        $this->assertSame(1, CrmWebhookDelivery::whereNotNull('delivered_at')->count());
        $this->travel(61)->seconds();
        $this->artisan('crm:deliver-webhooks')->assertSuccessful();
        $this->assertSame(0, CrmWebhookDelivery::whereNull('delivered_at')->count());
        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => $request->url() === 'https://crm.example.test/payments');
    }

    public function test_outbox_failure_rolls_back_the_entire_privacy_change(): void
    {
        $lead = $this->lead();
        $this->mock(CrmWebhookOutbox::class)->shouldReceive('recordDeletion')->once()->andThrow(new RuntimeException('Outbox unavailable'));
        try {
            app(RecruitmentRegistrationPrivacy::class)->delete($lead);
            $this->fail('Expected the outbox failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Outbox unavailable', $exception->getMessage());
        }
        $this->assertDatabaseCount('recruitment_leads', 1);
        $this->assertDatabaseCount('crm_registration_tombstones', 0);
        $this->assertSame('registration.created', CrmWebhookDelivery::sole()->event_type);
    }

    public function test_privacy_commands_reject_other_sources_and_status_never_exposes_secrets(): void
    {
        $lead = $this->lead('another_form');
        $this->artisan('crm:revoke-whatsapp-consent', ['registration' => $lead->registrationId()])->assertFailed();
        $this->artisan('crm:delete-registration', ['registration' => $lead->registrationId(), '--force' => true])->assertFailed();
        $this->artisan('crm:integration-status')->expectsOutputToContain('"historical_registrations": 0')
            ->doesntExpectOutputToContain(self::TOKEN)
            ->doesntExpectOutputToContain(config('crm.webhook_secret'))->assertSuccessful();
    }

    private function lead(string $source = RecruitmentLead::SOURCE): RecruitmentLead
    {
        $owner = User::factory()->create();

        return DB::transaction(function () use ($owner, $source) {
            $lead = $owner->recruitmentLeads()->create([
                'source' => $source, 'source_slug' => $owner->landing_page_slug,
                'source_url' => route('subscriber-recruitment.show', $owner->landing_page_slug),
                'full_name' => 'Peserta Data Privat', 'phone_number' => '6281323456789',
                'domicile' => 'Jakarta', 'activity' => 'Karyawan', 'consented_at' => now(),
                'whatsapp_consent' => true, 'whatsapp_consented_at' => now(), 'whatsapp_consent_version' => '2026-11-13',
                'event_payment_status' => 'awaiting_payment', 'event_checkout_id' => (string) Str::uuid(),
            ]);
            app(CrmWebhookOutbox::class)->record($lead, 'registration.created');

            return $lead;
        });
    }
}
