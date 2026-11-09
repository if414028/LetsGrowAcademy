<?php

namespace Tests\Feature;

use App\Models\GrowAdCampaign;
use App\Models\GrowAdContent;
use App\Models\GrowAdSetting;
use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\GrowAdPaymentUpdater;
use App\Services\GrowAdPricing;
use App\Services\MidtransService;
use App\Services\RecruitmentEventCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GrowAdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['midtrans.server_key' => 'server-test', 'midtrans.client_key' => 'client-test']);
    }

    private function member(string $role = 'Health Planner', bool $subscribed = true): User
    {
        $user = User::factory()->create(['status' => 'Active', 'phone_number' => '081234567890']);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        if ($subscribed) {
            $user->subscriptions()->create(['duration_months' => 1, 'amount' => 50000, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
        }

        return $user;
    }

    private function content(array $data = []): GrowAdContent
    {
        return GrowAdContent::create($data + ['title' => 'Konten event', 'description' => 'Kenali peluang bersama pembimbing.', 'media_path' => 'grow-ads/test.webp', 'media_type' => 'image', 'is_active' => true]);
    }

    private function campaign(User $user, array $data = []): GrowAdCampaign
    {
        GrowAdSetting::firstOrCreate(['user_id' => $user->id], ['activated_at' => now()]);
        $campaign = GrowAdCampaign::create($data + ['user_id' => $user->id, 'status' => 'draft'] + app(GrowAdPricing::class)->quote(50000, 7));
        $content = $this->content();
        $campaign->placements()->create($content->only(['title', 'description', 'media_path', 'media_type']) + ['content_id' => $content->id]);

        return $campaign;
    }

    public function test_hp_and_hm_get_onboarding_and_explicit_idempotent_activation(): void
    {
        foreach (['Health Planner', 'Health Manager'] as $role) {
            $user = $this->member($role);
            $this->actingAs($user)->get(route('grow-ads.index'))->assertOk()->assertSee('Aktifkan Grow Ads');
            $this->assertDatabaseMissing('grow_ad_settings', ['user_id' => $user->id]);
            $this->post(route('grow-ads.activate'))->assertRedirect(route('grow-ads.create'));
            $this->post(route('grow-ads.activate'))->assertRedirect(route('grow-ads.create'));
            $this->assertSame(1, GrowAdSetting::where('user_id', $user->id)->count());
            $this->get(route('grow-ads.create'))->assertOk()->assertSee('Konten sedang disiapkan.');
        }
    }

    public function test_grow_ads_requires_an_active_hp_or_hm_subscription_and_admin_routes_require_admin(): void
    {
        $this->get(route('grow-ads.index'))->assertRedirect(route('login'));
        $this->actingAs($this->member(subscribed: false))->get(route('grow-ads.index'))->assertRedirect(route('subscriptions.index'));
        $this->actingAs($this->member('Sales Manager'))->get(route('grow-ads.index'))->assertForbidden();
        $this->actingAs($this->member())->get(route('admin.grow-ads.index'))->assertForbidden();
        $this->actingAs($this->member('Admin', false))->get(route('admin.grow-ads.index'))->assertOk();
    }

    public function test_selection_rejects_empty_more_than_three_duplicate_and_archived_contents(): void
    {
        $user = $this->member();
        GrowAdSetting::create(['user_id' => $user->id, 'activated_at' => now()]);
        $ids = collect(range(1, 4))->map(fn () => $this->content()->id)->all();
        $archived = $this->content(['is_active' => false]);
        foreach ([[], $ids, [$ids[0], $ids[0]], [$archived->id], [999999]] as $choice) {
            $this->actingAs($user)->post(route('grow-ads.select'), ['content_ids' => $choice])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('grow_ad_campaigns', 0);
        $this->post(route('grow-ads.select'), ['content_ids' => array_slice($ids, 0, 3)])->assertRedirect();
        $campaign = GrowAdCampaign::first();
        $this->assertSame(3, $campaign->placements()->count());
        $this->get(route('grow-ads.budget', $campaign))->assertOk()->assertSee('Biaya layanan aplikasi');
    }

    public function test_checkout_recalculates_all_costs_and_ignores_client_totals_and_duplicate_submits(): void
    {
        $user = $this->member();
        $campaign = $this->campaign($user);
        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createSnapToken')->once()->withArgs(function ($payload) {
                $this->assertSame(393500, $payload['transaction_details']['gross_amount']);
                $this->assertSame(393500, array_sum(array_column($payload['item_details'], 'price')));
                $this->assertSame('Biaya layanan aplikasi', $payload['item_details'][2]['name']);
                $this->assertSame(route('grow-ads.payment-finish'), $payload['callbacks']['finish']);

                return true;
            })->andReturn('snap-grow-ads');
        });
        $payload = ['daily_budget' => 50000, 'duration_days' => 7, 'total_amount' => 1, 'service_fee' => 0, 'payment_status' => 'paid'];
        $this->actingAs($user)->post(route('grow-ads.checkout', $campaign), $payload)->assertRedirect(route('grow-ads.show', $campaign));
        $this->post(route('grow-ads.checkout', $campaign), $payload)->assertRedirect();
        $this->assertDatabaseHas('grow_ad_campaigns', ['id' => $campaign->id, 'total_amount' => 393500, 'vat_amount' => 38500, 'service_fee' => 5000, 'payment_status' => 'pending', 'status' => 'awaiting_payment']);
        $this->get(route('grow-ads.show', $campaign))->assertOk()->assertSee('Bayar melalui Midtrans');
    }

    public function test_another_user_cannot_select_reserved_content_or_pay_for_a_stale_draft(): void
    {
        $first = $this->member();
        $second = $this->member();
        $content = $this->content(['title' => 'Thai Joy']);
        $choice = ['content_ids' => [$content->id]];

        $this->actingAs($first)->post(route('grow-ads.activate'));
        $this->post(route('grow-ads.select'), $choice)->assertRedirect();
        $draft = GrowAdCampaign::where('user_id', $first->id)->sole();
        $this->get(route('grow-ads.create'))->assertViewHas('reservedIds', []);

        $this->actingAs($second)->post(route('grow-ads.activate'));
        $this->get(route('grow-ads.create'))->assertSee('Thai Joy')->assertSee('Sudah dipilih pengguna lain')
            ->assertDontSee('name="content_ids[]"', false);
        $this->from(route('grow-ads.create'))->post(route('grow-ads.select'), $choice)->assertSessionHasErrors('content_ids');
        $this->assertDatabaseCount('grow_ad_campaigns', 1);

        $this->travel(25)->hours();
        $this->post(route('grow-ads.select'), $choice)->assertSessionHasNoErrors()->assertRedirect();
        $secondDraft = GrowAdCampaign::where('user_id', $second->id)->sole();
        $secondDraft->update(['status' => 'preparing', 'payment_status' => 'paid']);

        $this->actingAs($first)->post(route('grow-ads.checkout', $draft), ['daily_budget' => 50000, 'duration_days' => 7])
            ->assertRedirect(route('grow-ads.create'))->assertSessionHasErrors('content_ids');
        $this->assertNull($draft->fresh()->midtrans_order_id);
        $secondDraft->update(['status' => 'active', 'ends_at' => now()->subMinute()]);
        $this->get(route('grow-ads.create'))->assertViewHas('reservedIds', []);
    }

    public function test_invalid_budget_unavailable_contents_and_missing_gateway_cannot_create_payment(): void
    {
        $user = $this->member();
        $campaign = $this->campaign($user);
        $this->actingAs($user)->post(route('grow-ads.checkout', $campaign), ['daily_budget' => -1, 'duration_days' => 0])->assertSessionHasErrors(['daily_budget', 'duration_days']);
        GrowAdContent::query()->update(['is_active' => false]);
        $this->post(route('grow-ads.checkout', $campaign), ['daily_budget' => 50000, 'duration_days' => 7])->assertSessionHasErrors('content_ids');
        config(['midtrans.server_key' => null]);
        $this->post(route('grow-ads.checkout', $campaign), ['daily_budget' => 50000, 'duration_days' => 7])->assertSessionHas('error');
        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_owners_cannot_view_modify_or_sync_each_others_campaigns(): void
    {
        $owner = $this->member();
        $campaign = $this->campaign($owner);
        $this->actingAs($this->member());
        $this->get(route('grow-ads.show', $campaign))->assertForbidden();
        $this->get(route('grow-ads.budget', $campaign))->assertForbidden();
        $this->post(route('grow-ads.checkout', $campaign), ['daily_budget' => 50000, 'duration_days' => 7])->assertForbidden();
        $this->post(route('grow-ads.sync-payment', $campaign))->assertForbidden();
    }

    private function gatewayStatus(GrowAdCampaign $campaign, string $status = 'settlement', array $data = []): object
    {
        return (object) ($data + ['order_id' => $campaign->midtrans_order_id, 'gross_amount' => (string) $campaign->total_amount, 'transaction_status' => $status, 'payment_type' => 'qris']);
    }

    public function test_signed_verified_webhook_prepares_campaign_only_once_and_late_pending_cannot_undo_payment(): void
    {
        $campaign = $this->campaign($this->member(), ['status' => 'awaiting_payment', 'midtrans_order_id' => 'GADS-test']);
        $this->mock(MidtransService::class, fn ($mock) => $mock->shouldReceive('transactionStatus')->twice()->andReturn($this->gatewayStatus($campaign)));
        $payload = ['order_id' => 'GADS-test', 'status_code' => '200', 'gross_amount' => '393500.00'];
        $payload['signature_key'] = hash('sha512', implode('', $payload).'server-test');
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $paidAt = $campaign->fresh()->paid_at;
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->assertSame('preparing', $campaign->fresh()->status);
        $this->assertTrue($paidAt->equalTo($campaign->fresh()->paid_at));
        $campaign->update(['status' => 'active']);
        app(GrowAdPaymentUpdater::class)->update($campaign, $this->gatewayStatus($campaign, 'expire'));
        $this->assertSame('active', $campaign->fresh()->status);
        $this->assertSame('paid', $campaign->fresh()->payment_status);
        app(GrowAdPaymentUpdater::class)->update($campaign, $this->gatewayStatus($campaign, 'refund'));
        app(GrowAdPaymentUpdater::class)->update($campaign, $this->gatewayStatus($campaign));
        $this->assertSame('cancelled', $campaign->fresh()->status);
        $this->assertSame('refunded', $campaign->fresh()->payment_status);
    }

    public function test_gateway_mismatch_or_forged_browser_success_cannot_mark_payment_paid(): void
    {
        $user = $this->member();
        $campaign = $this->campaign($user, ['status' => 'awaiting_payment', 'midtrans_order_id' => 'GADS-bad']);
        $this->mock(MidtransService::class, fn ($mock) => $mock->shouldReceive('transactionStatus')->once()->andReturn($this->gatewayStatus($campaign, data: ['gross_amount' => '1.00'])));
        $this->actingAs($user)->get(route('grow-ads.payment-finish', ['order_id' => 'GADS-bad', 'transaction_status' => 'settlement']))->assertSessionHas('error');
        $this->assertSame('pending', $campaign->fresh()->payment_status);
    }

    private function adminPayload(GrowAdCampaign $campaign, array $extra = []): array
    {
        return $extra + ['status' => 'active', 'placements' => $campaign->placements->map(fn ($p) => ['id' => $p->id, 'meta_ad_id' => '987654321', 'pixel_id' => '123456789', 'impressions' => null, 'clicks' => null])->all()];
    }

    public function test_admin_must_complete_paid_campaign_setup_before_launch_and_duration_never_restarts(): void
    {
        $owner = $this->member();
        $campaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $this->actingAs($this->member('Admin', false));
        $this->get(route('admin.grow-ads.show', $campaign))->assertOk()
            ->assertSee('Tautan tujuan iklan')->assertSee('name="placements[0][pixel_id]"', false);
        $payload = $this->adminPayload($campaign);
        $payload['placements'][0]['pixel_id'] = null;
        $this->put(route('admin.grow-ads.update', $campaign), $payload)->assertSessionHasErrors('placements.0.pixel_id');
        $this->assertNull($campaign->fresh()->starts_at);
        $this->put(route('admin.grow-ads.update', $campaign), $this->adminPayload($campaign))->assertRedirect();
        $this->assertSame('123456789', $campaign->placements()->first()->pixel_id);
        $start = $campaign->fresh()->starts_at;
        $this->assertTrue($campaign->fresh()->ends_at->equalTo($start->copy()->addDays(7)));
        $this->travel(1)->hours();
        $this->put(route('admin.grow-ads.update', $campaign), $this->adminPayload($campaign))->assertRedirect();
        $this->assertTrue($campaign->fresh()->starts_at->equalTo($start));
        $this->assertNull($campaign->fresh()->metrics_updated_at);
        $campaign->update(['payment_status' => 'pending']);
        $this->put(route('admin.grow-ads.update', $campaign), $this->adminPayload($campaign))->assertStatus(422);
    }

    public function test_admin_content_upload_edit_and_archive_keep_campaign_snapshot(): void
    {
        Storage::fake('public');
        $campaign = $this->campaign($this->member());
        $content = GrowAdContent::first();
        $this->actingAs($this->member('Head Admin', false));
        $data = ['title' => 'Konten baru', 'description' => 'Deskripsi', 'is_active' => 1];
        $this->post(route('admin.grow-ads.contents.store'), $data + ['media' => UploadedFile::fake()->image('ad.jpg')])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.grow-ads.contents'));
        $uploaded = GrowAdContent::latest('id')->first();
        Storage::disk('public')->assertExists($uploaded->media_path);
        $this->put(route('admin.grow-ads.contents.update', $content), array_replace($data, ['is_active' => 0]))->assertRedirect();
        $this->assertFalse($content->fresh()->is_active);
        $this->assertSame('Konten event', $campaign->placements()->first()->title);
        $this->get(route('admin.grow-ads.contents'))->assertOk();
        $this->get(route('admin.grow-ads.contents.edit', $content))->assertOk();
    }

    public function test_catalogues_show_latest_uploads_first_regardless_of_legacy_sort_order(): void
    {
        $this->freezeTime();
        $older = $this->content();
        $newer = $this->content();
        $archived = $this->content(['is_active' => false]);
        DB::table('grow_ad_contents')->where('id', $newer->id)->update(['sort_order' => 9]);

        $this->actingAs($this->member('Head Admin', false))
            ->get(route('admin.grow-ads.contents'))
            ->assertOk()
            ->assertViewHas('contents', fn ($contents) => $contents->pluck('id')->all() === [$archived->id, $newer->id, $older->id]);

        $member = $this->member();
        GrowAdSetting::create(['user_id' => $member->id, 'activated_at' => now()]);
        $this->actingAs($member)->get(route('grow-ads.create'))
            ->assertOk()
            ->assertViewHas('contents', fn ($contents) => $contents->pluck('id')->all() === [$newer->id, $older->id]);
    }

    public function test_signed_landing_link_preserves_owner_and_content_attribution_through_form(): void
    {
        $owner = $this->member();
        $campaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $placement = $campaign->placements()->first();
        $secondContent = $this->content();
        $secondPlacement = $campaign->placements()->create($secondContent->only(['title', 'description', 'media_path', 'media_type']) + ['content_id' => $secondContent->id]);
        $payload = $this->adminPayload($campaign);
        $payload['placements'][1]['meta_ad_id'] = '987654322';
        $payload['placements'][1]['pixel_id'] = '234567891';
        $this->actingAs($this->member('Admin', false))
            ->put(route('admin.grow-ads.update', $campaign), $payload)->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))->assertDontSee('fbevents.js')->assertDontSee('AddPaymentInfo');
        $this->get($secondPlacement->landingUrl())->assertOk()->assertSee('234567891')->assertDontSee('123456789');
        $response = $this->get($placement->landingUrl())->assertOk()->assertSee('123456789')->assertDontSee('234567891')
            ->assertSee('AddPaymentInfo')->assertSee('AddToCart')->assertDontSee("'Purchase'", false);
        $token = $response->viewData('growAdToken');
        $this->assertNotNull($token);
        $this->get($placement->landingUrl().'&fbclid=meta-click-id&utm_source=facebook')->assertViewHas('growAdToken', fn ($value) => is_string($value));
        $this->get($placement->landingUrl().'&creative=999999')->assertViewHas('growAdToken', null)->assertDontSee('fbevents.js');
        $clickUrl = route('subscriber-recruitment.form-click', $owner->landing_page_slug);
        $this->postJson($clickUrl, ['grow_ad_token' => 'forged'])->assertNoContent();
        $other = $this->member();
        $this->postJson(route('subscriber-recruitment.form-click', $other->landing_page_slug), ['grow_ad_token' => $token])->assertNoContent();
        $this->assertSame(0, $placement->fresh()->form_cta_clicks);
        $this->postJson($clickUrl, ['grow_ad_token' => $token])->assertNoContent();
        $this->assertSame(1, $placement->fresh()->form_cta_clicks);
        $this->assertSame(0, $secondPlacement->fresh()->form_cta_clicks);
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->registration() + ['grow_ad_token' => $token, 'owner_user_id' => 9999])->assertStatus(303);
        $this->assertDatabaseHas('recruitment_leads', ['owner_user_id' => $owner->id, 'grow_ad_placement_id' => $placement->id]);
        $lead = RecruitmentLead::sole();
        $success = route('subscriber-recruitment.thank-you', [$owner->landing_page_slug, $lead->event_checkout_id]);
        $this->get($success)->assertRedirect()->assertDontSee('CompleteRegistration')->assertDontSee('Purchase');
        $this->patch(route('admin.grow-ads.leads.update', [$campaign, $lead]), ['event_payment_status' => 'paid'])
            ->assertUnprocessable();
        $this->actingAs($owner)->get(route('grow-ads.show', $campaign))
            ->assertViewHas('registeredCount', 0)->assertSee('Menunggu pembayaran')
            ->assertViewHas('funnel', ['AddPaymentInfo' => 1, 'AddToCart' => 1, 'Purchase' => 0]);
        $payment = $lead->eventPayments()->create([
            'midtrans_order_id' => 'REVT-test', 'class_amount' => 88000, 'service_fee' => 5000, 'checkout_url' => 'https://example.test',
        ]);
        app(RecruitmentEventCheckout::class)->update($payment, (object) [
            'order_id' => 'REVT-test', 'gross_amount' => '93000.00', 'status_code' => '200', 'transaction_status' => 'settlement',
        ]);
        $this->get(route('grow-ads.show', $campaign))->assertViewHas('registeredCount', 1)->assertViewHas('paidCount', 1)
            ->assertViewHas('funnel', ['AddPaymentInfo' => 1, 'AddToCart' => 1, 'Purchase' => 1]);
        $campaign->update(['ends_at' => now()->subMinute()]);
        $this->get($success)->assertSee('CompleteRegistration')->assertSee('123456789')->assertDontSee('234567891')
            ->assertSee('Purchase')->assertViewHas('purchase', ['value' => 93000, 'currency' => 'IDR']);
        $this->get($success)->assertOk()->assertDontSee('CompleteRegistration')->assertDontSee('Purchase');

        $this->post(route('subscriber-recruitment.submit', $other->landing_page_slug), $this->registration() + ['grow_ad_token' => $token])->assertStatus(303);
        $this->assertDatabaseHas('recruitment_leads', ['owner_user_id' => $other->id, 'grow_ad_placement_id' => null]);
        $this->get(route('subscriber-recruitment.thank-you', [$other->landing_page_slug, $lead->event_checkout_id]))->assertNotFound();
        $this->get(route('subscriber-recruitment.show', ['slug' => $owner->landing_page_slug, 'grow_ad' => $campaign->uuid, 'creative' => $placement->id]))->assertViewHas('growAdToken', null)->assertDontSee('fbevents.js');
        $this->get($placement->landingUrl())->assertViewHas('growAdToken', null)->assertDontSee('fbevents.js');
    }

    private function registration(): array
    {
        return ['full_name' => 'Peserta Event', 'phone_number' => '081323456789', 'domicile' => 'Jakarta', 'activity' => config('recruitment.activities')[0], 'consent' => '1'];
    }

    public function test_metrics_validation_is_atomic_and_successful_update_is_visible_to_owner(): void
    {
        $owner = $this->member();
        $campaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $placement = $campaign->placements()->first();
        $this->actingAs($this->member('Admin', false));
        $payload = $this->adminPayload($campaign, ['status' => 'preparing']);
        $payload['placements'][0]['impressions'] = 10;
        $payload['placements'][0]['clicks'] = 11;
        $this->put(route('admin.grow-ads.update', $campaign), $payload)->assertSessionHasErrors('placements');
        $this->assertNull($placement->fresh()->impressions);
        $payload['placements'][0]['clicks'] = 2;
        $this->put(route('admin.grow-ads.update', $campaign), $payload)->assertRedirect();
        $this->assertNotNull($campaign->fresh()->metrics_updated_at);
        $this->assertSame(10, $placement->fresh()->impressions);
        $this->actingAs($owner)->get(route('grow-ads.show', $campaign))->assertOk()->assertSee('Data Meta Ads diperbarui Admin');
    }

    public function test_dashboard_has_no_fabricated_metrics_and_only_shows_its_campaign_leads(): void
    {
        $owner = $this->member();
        $campaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $otherCampaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $lead = $owner->recruitmentLeads()->create(['source' => RecruitmentLead::SOURCE, 'source_slug' => $owner->landing_page_slug, 'source_url' => 'https://example.test', 'full_name' => 'Peserta Milik Kampanye', 'phone_number' => '628123456789', 'domicile' => 'Jakarta', 'activity' => 'Karyawan', 'consented_at' => now(), 'grow_ad_placement_id' => $campaign->placements()->first()->id]);
        $this->actingAs($owner)->get(route('grow-ads.show', $campaign))->assertOk()->assertSee('Peserta Milik Kampanye')->assertSee('belum lengkap')->assertViewHas('registeredCount', 1)->assertViewHas('paidCount', 0);
        $this->get(route('grow-ads.show', $otherCampaign))->assertDontSee('Peserta Milik Kampanye')->assertViewHas('registeredCount', 0);
        $this->actingAs($this->member('Admin', false))->patch(route('admin.grow-ads.leads.update', [$otherCampaign, $lead]), ['event_payment_status' => 'paid'])->assertNotFound();
        $this->patch(route('admin.grow-ads.leads.update', [$campaign, $lead]), ['event_payment_status' => 'paid'])->assertRedirect();
        $this->actingAs($owner)->get(route('grow-ads.show', $campaign))->assertViewHas('paidCount', 1)->assertSee('Sudah dibayar');
    }

    public function test_paid_direct_registrations_appear_in_the_owner_list_without_ad_attribution(): void
    {
        $owner = $this->member();
        $other = $this->member();
        $campaign = $this->campaign($owner, ['status' => 'preparing', 'payment_status' => 'paid', 'paid_at' => now()]);
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->registration())->assertStatus(303);
        $lead = RecruitmentLead::sole();
        $lead->update(['full_name' => 'Peserta Langsung Sudah Bayar', 'event_payment_status' => 'paid', 'event_paid_at' => now()]);
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), array_replace($this->registration(), ['full_name' => 'Peminat Langsung Belum Bayar']))->assertStatus(303);
        $this->post(route('subscriber-recruitment.submit', $other->landing_page_slug), array_replace($this->registration(), ['full_name' => 'Peserta Langsung Pemilik Lain']))->assertStatus(303);
        $other->recruitmentLeads()->first()->update(['event_payment_status' => 'paid']);

        $this->actingAs($owner)->get(route('grow-ads.show', $campaign))->assertOk()
            ->assertSee('Peserta Langsung Sudah Bayar')->assertSee('Landing page langsung')
            ->assertDontSee('Peminat Langsung Belum Bayar')->assertDontSee('Peserta Langsung Pemilik Lain')
            ->assertViewHas('registeredCount', 0)->assertViewHas('paidCount', 0);
        $this->actingAs($this->member('Admin', false))->get(route('admin.grow-ads.show', $campaign))->assertOk()
            ->assertSee('Peserta Langsung Sudah Bayar')->assertDontSee('Peserta Langsung Pemilik Lain');
    }
}
