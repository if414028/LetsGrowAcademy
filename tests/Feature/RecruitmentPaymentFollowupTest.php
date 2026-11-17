<?php

namespace Tests\Feature;

use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\CrmWebhookOutbox;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruitmentPaymentFollowupTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_distinguishes_form_submission_from_an_actual_pending_transaction(): void
    {
        $owner = $this->subscriber();
        $beforeCheckout = $this->lead($owner, ['event_checkout_id' => (string) Str::uuid(), 'event_payment_status' => 'awaiting_payment']);
        $pending = $this->lead($owner, ['event_checkout_id' => (string) Str::uuid(), 'event_payment_status' => 'awaiting_payment']);
        $paid = $this->lead($owner, ['event_checkout_id' => (string) Str::uuid(), 'event_payment_status' => 'paid']);
        foreach ([$pending, $paid] as $lead) {
            $lead->eventPayments()->create([
                'midtrans_order_id' => 'REVT-'.Str::uuid(), 'class_amount' => 88000, 'service_fee' => 4440,
                'checkout_url' => 'https://gateway.example/test', 'status' => 'pending',
            ]);
        }
        $response = $this->actingAs($owner)->get(route('subscriber-recruitment.leads'))->assertOk();
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        foreach ([$beforeCheckout->id => 'Belum dibayar', $pending->id => 'Menunggu pembayaran', $paid->id => 'Sudah dibayar'] as $id => $expected) {
            $this->assertSame($expected, trim($xpath->evaluate('string(//article[@aria-labelledby="lead-name-'.$id.'"]//strong[contains(@class,"recruitment-lead-payment-badge")])')));
        }
        $response->assertSee($beforeCheckout->continuePaymentUrl(), false)
            ->assertSee('Salin link pembayaran')->assertSee('Follow-up WhatsApp');
        $this->assertStringNotContainsString($paid->event_checkout_id, $response->getContent());
    }

    public function test_viewing_legacy_registrations_never_creates_checkout_links_or_events(): void
    {
        $owner = $this->subscriber();
        $lead = $this->lead($owner);
        $this->actingAs($owner)->get(route('subscriber-recruitment.leads'))
            ->assertOk()->assertSee('Buat link pembayaran');
        $this->assertNull($lead->fresh()->event_checkout_id);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
    }

    public function test_owner_can_prepare_a_stable_legacy_link_with_crm_recovery_without_creating_a_transaction(): void
    {
        $this->freezeTime();
        config(['app.url' => 'https://academy.example', 'crm.api_token' => str_repeat('api-test-', 5)]);
        $owner = $this->subscriber();
        $lead = $this->lead($owner, ['whatsapp_consent' => false]);
        $this->mock(MidtransService::class)->shouldNotReceive('createSnapTransaction');
        $this->travel(1)->minutes();
        $params = ['q' => 'Peserta', 'page' => 2];
        $url = route('subscriber-recruitment.payment-link', $lead->id);
        $this->actingAs($owner)->post($url, $params)->assertStatus(303)
            ->assertRedirect(route('subscriber-recruitment.leads', $params).'#lead-name-'.$lead->id)
            ->assertSessionHas('payment_link_ready', $lead->id);
        $lead->refresh();
        $checkoutId = $lead->event_checkout_id;
        $this->assertTrue(Str::isUuid($checkoutId));
        $this->assertFalse($lead->whatsapp_consent);
        $this->assertSame('unconfirmed', $lead->event_payment_status);
        $this->assertNull($lead->seminar_id);
        $this->assertSame('registration.updated', CrmWebhookDelivery::sole()->event_type);
        $this->assertSame($lead->continuePaymentUrl(), CrmWebhookDelivery::sole()->payload['data']['continue_payment_url']);
        $this->assertDatabaseCount('recruitment_event_payments', 0);

        $this->post($url)->assertStatus(303);
        $this->assertSame($checkoutId, $lead->fresh()->event_checkout_id);
        $this->assertDatabaseCount('crm_webhook_deliveries', 1);
        $this->get($lead->continuePaymentUrl())->assertOk()->assertSee('Metode pembayaran');
        $this->withToken(config('crm.api_token'))->getJson('https://localhost/api/v1/crm/registrations/'.$lead->registrationId())
            ->assertOk()->assertJsonPath('data.continue_payment_url', $lead->continuePaymentUrl())
            ->assertJsonPath('data.payment', null);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
    }

    public function test_only_the_subscribed_owner_can_prepare_links_for_their_own_recruitment_source(): void
    {
        $owner = $this->subscriber();
        $other = $this->subscriber();
        $lead = $this->lead($owner);
        $otherSource = $this->lead($owner, ['source' => 'other_form']);
        $url = route('subscriber-recruitment.payment-link', $lead->id);
        $this->post($url)->assertRedirect(route('login'));
        $this->actingAs($other)->post($url)->assertNotFound();
        $this->actingAs($owner)->post(route('subscriber-recruitment.payment-link', $otherSource->id))->assertNotFound();
        $owner->subscriptions()->update(['ends_at' => now()->subDay()]);
        $this->post($url)->assertRedirect(route('subscriptions.index'));
        $this->assertNull($lead->fresh()->event_checkout_id);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    public function test_settled_or_refunded_registrations_cannot_receive_new_payment_links(): void
    {
        $owner = $this->subscriber();
        $this->actingAs($owner);
        foreach (['paid', 'refunded'] as $status) {
            $lead = $this->lead($owner, ['event_payment_status' => $status]);
            $this->post(route('subscriber-recruitment.payment-link', $lead->id))->assertStatus(409);
            $this->assertNull($lead->fresh()->event_checkout_id);
            $this->assertNull($lead->continuePaymentUrl());
            $this->assertNull($lead->paymentFollowupWhatsappUrl());
        }
        $this->get(route('subscriber-recruitment.leads'))->assertOk()
            ->assertDontSee('Buat link pembayaran')->assertDontSee('Salin link pembayaran')->assertDontSee('Follow-up WhatsApp');
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    public function test_preparing_an_existing_link_does_not_rotate_it_or_create_duplicate_events(): void
    {
        $owner = $this->subscriber();
        $checkoutId = (string) Str::uuid();
        $lead = $this->lead($owner, ['event_checkout_id' => $checkoutId]);
        $this->actingAs($owner)->post(route('subscriber-recruitment.payment-link', $lead->id))->assertStatus(303);
        $this->assertSame($checkoutId, $lead->fresh()->event_checkout_id);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
    }

    public function test_crm_outbox_failure_rolls_back_prepared_link(): void
    {
        $owner = $this->subscriber();
        $lead = $this->lead($owner);
        $this->mock(CrmWebhookOutbox::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test outbox unavailable'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($owner)->post(route('subscriber-recruitment.payment-link', $lead->id));
            $this->fail('Expected outbox failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Test outbox unavailable', $exception->getMessage());
        }
        $this->assertNull($lead->fresh()->event_checkout_id);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
        $this->assertDatabaseCount('recruitment_event_payments', 0);
    }

    public function test_whatsapp_draft_contains_the_canonical_private_link_and_code_without_changing_consent(): void
    {
        config(['app.url' => 'https://academy.example']);
        $lead = $this->lead($this->subscriber(), [
            'full_name' => 'Rina & Budi', 'event_checkout_id' => (string) Str::uuid(), 'whatsapp_consent' => false,
        ]);
        $url = $lead->paymentFollowupWhatsappUrl();
        $this->assertSame('wa.me', parse_url($url, PHP_URL_HOST));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertStringContainsString('Halo Rina & Budi,', $query['text']);
        $this->assertStringContainsString($lead->continuePaymentUrl(), $query['text']);
        $this->assertStringContainsString('Kode pendaftaran: '.$lead->registrationId(), $query['text']);
        $this->assertFalse($lead->fresh()->whatsapp_consent);
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    private function subscriber(): User
    {
        $user = User::factory()->create(['status' => 'Active', 'phone_number' => '081234567890', 'dst_code' => 'DST001']);
        $user->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));
        $user->subscriptions()->create([
            'duration_months' => 3, 'amount' => 150000, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }

    private function lead(User $owner, array $overrides = []): RecruitmentLead
    {
        return $owner->recruitmentLeads()->create(array_merge([
            'source' => RecruitmentLead::SOURCE, 'source_slug' => $owner->landing_page_slug,
            'source_url' => route('subscriber-recruitment.show', $owner->landing_page_slug),
            'full_name' => 'Peserta Contoh', 'phone_number' => '6281234567890', 'domicile' => 'Bandung', 'activity' => 'Karyawan',
            'consented_at' => now(), 'event_payment_status' => 'unconfirmed',
        ], $overrides));
    }
}
