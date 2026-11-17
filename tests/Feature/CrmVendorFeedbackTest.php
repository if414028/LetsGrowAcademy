<?php

namespace Tests\Feature;

use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\CrmWhatsappLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmVendorFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'crm-feedback-test-token-with-32-characters';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'crm.environment' => 'staging', 'crm.api_token' => self::TOKEN,
            'crm.require_https' => true, 'crm.whatsapp_number' => null,
            'crm.webhooks_enabled' => false,
            'recruitment.event' => ['seminar_id' => null, 'seminar_name' => null, 'batch_id' => null, 'batch_starts_at' => null],
        ]);
        Http::preventStrayRequests();
    }

    public function test_one_active_batch_is_snapshotted_for_all_owners_and_browser_fields_cannot_override_it(): void
    {
        config(['recruitment.event' => [
            'seminar_id' => 'SEM-01', 'seminar_name' => 'Seminar Pengantar',
            'batch_id' => 'BATCH-NOV-01', 'batch_starts_at' => '2026-11-25T19:00:00+07:00',
        ]]);
        $first = $this->subscriber();
        $second = $this->subscriber();
        $this->get(route('subscriber-recruitment.show', $first->landing_page_slug))->assertOk()
            ->assertSee('Seminar Pengantar')->assertSee('BATCH-NOV-01')->assertSee('25 November 2026, 19:00 WIB');
        foreach ([$first, $second] as $owner) {
            $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form() + [
                'seminar_id' => 'FORGED', 'batch_id' => 'FORGED', 'batch_starts_at' => '2099-01-01T00:00:00Z',
            ])->assertStatus(303);
        }
        $lead = RecruitmentLead::oldest('id')->first();
        $this->assertSame('SEM-01', $lead->seminar_id);
        $this->assertSame('BATCH-NOV-01', $lead->batch_id);
        $this->assertSame('2026-11-25T12:00:00.000000Z', $lead->batch_starts_at->toISOString());
        $this->assertSame(2, RecruitmentLead::where('batch_id', 'BATCH-NOV-01')->count());
        config(['recruitment.event.batch_id' => 'BATCH-DEC-01', 'recruitment.event.seminar_name' => 'Seminar Berikutnya']);
        $this->withToken(self::TOKEN)->getJson('https://localhost/api/v1/crm/registrations/'.$lead->registrationId())
            ->assertOk()->assertJsonPath('data.batch_id', 'BATCH-NOV-01')
            ->assertJsonPath('data.seminar_name', 'Seminar Pengantar')
            ->assertJsonPath('data.batch_starts_at', '2026-11-25T12:00:00.000000Z');
        $event = CrmWebhookDelivery::where('recruitment_lead_id', $lead->id)->sole();
        $this->assertSame('1.1', $event->payload['schema_version']);
        $this->assertSame('staging', $event->payload['environment']);
        $this->assertSame('staging', $event->payload['data']['environment']);
        $this->assertSame('BATCH-NOV-01', $event->payload['data']['batch_id']);
    }

    public function test_sync_watermark_is_utc_and_accepted_with_or_without_fractional_seconds(): void
    {
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $url = 'https://localhost/api/v1/crm/registrations';
        $response = $this->withToken(self::TOKEN)->getJson($url)->assertOk()
            ->assertJsonPath('meta.environment', 'staging')->assertJsonPath('meta.schema_version', '1.1');
        $watermark = $response->json('meta.sync_started_at');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $watermark);
        $this->getJson($url.'?updated_since='.urlencode($watermark))->assertOk();
        $this->getJson($url.'?updated_since='.urlencode(now()->utc()->toISOString()))->assertOk();
        $this->getJson($url.'?updated_since=2026-11-17T04%3A00%3A00.123Z')->assertOk();
        foreach (['2026-02-30T10:00:00Z', '2026-11-17T25:00:00Z', '2026-11-17', 'tomorrow'] as $invalid) {
            $this->getJson($url.'?updated_since='.urlencode($invalid))->assertUnprocessable();
        }
    }

    public function test_continuation_link_uses_the_academy_page_and_disappears_when_paid_or_refunded(): void
    {
        config(['app.url' => 'https://academy.example']);
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $lead = RecruitmentLead::sole();
        $api = 'https://localhost/api/v1/crm/registrations/'.$lead->registrationId();
        $this->withToken(self::TOKEN);
        foreach (['awaiting_payment', 'failed', 'expired'] as $status) {
            $lead->update(['event_payment_status' => $status]);
            $response = $this->getJson($api)->assertOk();
            $this->assertSame($lead->continuePaymentUrl(), $response->json('data.continue_payment_url'));
            $this->assertStringStartsWith('https://academy.example/rekrutmen/', $response->json('data.continue_payment_url'));
            $this->assertStringContainsString('/rekrutmen/'.$lead->source_slug.'/pembayaran/'.$lead->event_checkout_id, $response->json('data.continue_payment_url'));
        }
        foreach (['paid', 'refunded'] as $status) {
            $lead->update(['event_payment_status' => $status]);
            $this->getJson($api)->assertOk()->assertJsonPath('data.continue_payment_url', null);
        }
        $lead->update(['event_payment_status' => 'unconfirmed', 'event_checkout_id' => null]);
        $this->getJson($api)->assertOk()->assertJsonPath('data.continue_payment_url', null)
            ->assertJsonPath('data.batch_id', null)->assertJsonPath('data.batch_starts_at', null);
    }

    public function test_whatsapp_buttons_use_verified_registration_state_and_encoded_messages(): void
    {
        config(['crm.whatsapp_number' => '+62 812-3456-7890']);
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $lead = RecruitmentLead::sole();
        $parameters = [$lead->source_slug, $lead->event_checkout_id];
        $checkout = route('subscriber-recruitment.payment.show', $parameters);
        $pending = 'Halo, saya ingin melanjutkan pembayaran. Kode: '.$lead->registrationId();
        $this->get($checkout.'?transaction_status=settlement')->assertOk()->assertSee('Chat via WhatsApp')
            ->assertSee('https://wa.me/6281234567890?text='.rawurlencode($pending), false)
            ->assertSee('Tekan Kirim di WhatsApp')->assertDontSee('Nama Peserta Uji');
        $this->assertSame('awaiting_payment', $lead->fresh()->event_payment_status);
        $this->assertNull($lead->fresh()->whatsapp_consent_revoked_at);
        $this->assertFalse($lead->fresh()->whatsapp_consent);

        $lead->update(['event_payment_status' => 'failed']);
        $failed = 'Halo, pembayaran saya gagal. Kode: '.$lead->registrationId();
        $this->get($checkout)->assertOk()->assertSee('https://wa.me/6281234567890?text='.rawurlencode($failed), false);
        $lead->update(['event_payment_status' => 'paid']);
        $paid = 'Halo, saya sudah daftar seminar. Kode: '.$lead->registrationId();
        $this->get(route('subscriber-recruitment.thank-you', $parameters))->assertOk()
            ->assertSee('Chat via WhatsApp')->assertSee('https://wa.me/6281234567890?text='.rawurlencode($paid), false)
            ->assertSee($lead->registrationId())->assertDontSee('admin akan menghubungi');
    }

    public function test_missing_or_invalid_business_numbers_never_render_a_broken_whatsapp_link(): void
    {
        $owner = $this->subscriber();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form());
        $lead = RecruitmentLead::sole();
        foreach ([null, '', 'javascript:alert(6281234567890)', '12345', '6281fake234567890'] as $number) {
            config(['crm.whatsapp_number' => $number]);
            $this->assertNull(app(CrmWhatsappLink::class)->forRegistration($lead));
        }
        $this->get(route('subscriber-recruitment.payment.show', [$lead->source_slug, $lead->event_checkout_id]))
            ->assertOk()->assertDontSee('wa.me/', false)->assertDontSee('Chat via WhatsApp');
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

    private function form(): array
    {
        return ['full_name' => 'Nama Peserta Uji', 'phone_number' => '081323456789', 'domicile' => 'Bandung', 'activity' => 'Karyawan', 'consent' => '1'];
    }
}
