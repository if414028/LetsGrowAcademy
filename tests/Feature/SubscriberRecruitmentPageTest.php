<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriberRecruitmentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_preview_and_share_their_own_recruitment_page(): void
    {
        $owner = $this->subscriber();

        $this->actingAs($owner)->get(route('subscriber-recruitment.index'))
            ->assertOk()
            ->assertSee('Landing Page Rekrutmen Anda')
            ->assertSee('Tampilan Landing Page Rekrutmen')
            ->assertSee('<iframe', false)
            ->assertSee(route('subscriber-recruitment.show', $owner->landing_page_slug), false)
            ->assertSee('Salin Link');
    }

    public function test_menu_and_management_page_require_an_active_subscription(): void
    {
        $nonSubscriber = User::factory()->create(['status' => 'Active']);
        $nonSubscriber->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));

        $this->get(route('subscriber-recruitment.index'))->assertRedirect(route('login'));
        $this->actingAs($nonSubscriber)->get(route('subscriber-recruitment.index'))
            ->assertRedirect(route('subscriptions.index'));
        $this->actingAs($nonSubscriber)->get(route('subscriptions.index'))
            ->assertOk()->assertDontSee('Landing Page Rekrutmen');

        $this->actingAs($this->subscriber())->get(route('subscriptions.index'))
            ->assertOk()->assertSee('Landing Page Rekrutmen')
            ->assertSee(route('subscriber-recruitment.index'), false);
    }

    public function test_guest_sees_content_and_contacts_belonging_to_the_page_owner(): void
    {
        $owner = $this->subscriber();

        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()
            ->assertSee('bangun sumber penghasilan baru.')
            ->assertSee('The Market')
            ->assertSee('The Success Path')
            ->assertSee('Nama lengkap')
            ->assertSee('Nadia Putri')
            ->assertSee('Health Planner')
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee(route('subscriber-recruitment.submit', $owner->landing_page_slug), false)
            ->assertDontSee('Gabrielle');
    }

    public function test_logged_in_visitor_does_not_replace_the_owner_of_a_shared_page(): void
    {
        $owner = $this->subscriber();
        $visitor = $this->subscriber(['name' => 'Visitor', 'full_name' => 'Other Subscriber', 'phone_number' => '089876543210']);

        $this->actingAs($visitor)->get(route('subscriber-recruitment.index'))
            ->assertSee(route('subscriber-recruitment.show', $visitor->landing_page_slug), false)
            ->assertDontSee(route('subscriber-recruitment.show', $owner->landing_page_slug), false);

        $this->actingAs($visitor)->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()->assertSee('Nadia Putri')
            ->assertSee('wa.me/6281234567890', false)
            ->assertDontSee('Other Subscriber')
            ->assertDontSee('wa.me/6289876543210', false);
    }

    public function test_missing_whatsapp_shows_profile_prompt_and_keeps_page_private(): void
    {
        $owner = $this->subscriber(['phone_number' => null]);

        $this->actingAs($owner)->get(route('subscriber-recruitment.index'))
            ->assertOk()->assertSee('Lengkapi nomor WhatsApp terlebih dahulu')
            ->assertDontSee('<iframe', false)->assertDontSee('Salin Link');
        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))->assertNotFound();
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->lead())->assertNotFound();
        $this->get(route('subscriber-recruitment.thank-you', $owner->landing_page_slug))->assertNotFound();
    }

    public function test_expired_pending_inactive_and_unknown_owners_cannot_receive_public_submissions(): void
    {
        $expired = $this->subscriber(['name' => 'Expired']);
        $expired->subscriptions()->update(['ends_at' => now()->subSecond()]);
        $pending = $this->subscriber(['name' => 'Pending']);
        $pending->subscriptions()->update(['status' => 'pending']);
        $inactive = $this->subscriber(['name' => 'Inactive Member', 'status' => 'Inactive']);
        $withoutSubscription = User::factory()->create(['name' => 'No subscription', 'phone_number' => '081234567890']);

        foreach ([$expired->landing_page_slug, $pending->landing_page_slug, $inactive->landing_page_slug, $withoutSubscription->landing_page_slug, 'unknown-member'] as $slug) {
            $this->get(route('subscriber-recruitment.show', $slug))->assertNotFound();
            $this->post(route('subscriber-recruitment.submit', $slug), $this->lead())->assertNotFound();
            $this->get(route('subscriber-recruitment.thank-you', $slug))->assertNotFound();
        }
    }

    public function test_valid_interest_form_redirects_to_the_url_owners_thank_you_page(): void
    {
        $owner = $this->subscriber();
        $other = $this->subscriber(['name' => 'Other', 'phone_number' => '089876543210']);

        $response = $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), [
            ...$this->lead(),
            'user_id' => $other->id,
            'whatsapp_url' => $other->whatsapp_url,
        ])->assertStatus(303)
            ->assertRedirect(route('subscriber-recruitment.thank-you', $owner->landing_page_slug));

        $location = $response->headers->get('Location');
        $this->assertNull(parse_url($location, PHP_URL_QUERY));
        $this->get($location)->assertOk()
            ->assertSee('Terima kasih sudah mendaftar!')
            ->assertSee('Dalam waktu dekat, admin akan menghubungi Anda untuk mengikuti event.')
            ->assertDontSee('wa.me', false)
            ->assertDontSee('Calon Mitra')
            ->assertDontSee('<form', false);
        $this->get($location)->assertOk();
        $this->assertDatabaseCount('recruitment_leads', 1);
        $this->assertDatabaseHas('recruitment_leads', ['owner_user_id' => $owner->id, 'full_name' => 'Calon Mitra']);
    }

    public function test_form_and_privacy_copy_explain_event_registration_and_admin_follow_up(): void
    {
        $owner = $this->subscriber();

        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()
            ->assertSee("disimpan di database Let's Grow Academy untuk didaftarkan ke dalam event", false)
            ->assertSee("Saya menyetujui penyimpanan data saya di database Let's Grow Academy untuk pendaftaran event", false)
            ->assertSee('Admin akan menghubungi Anda dalam waktu dekat.')
            ->assertDontSee('diarahkan ke WhatsApp')
            ->assertDontSee('pesan yang sudah terisi');
    }

    public function test_interest_topic_is_optional(): void
    {
        $owner = $this->subscriber();
        $data = $this->lead();
        unset($data['interest']);

        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $data)
            ->assertStatus(303)->assertSessionHasNoErrors();
    }

    public function test_form_requires_valid_contact_details_and_explicit_consent(): void
    {
        $owner = $this->subscriber();
        $url = route('subscriber-recruitment.show', $owner->landing_page_slug);

        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), [
            'full_name' => '', 'phone_number' => 'not-a-phone', 'domicile' => '', 'activity' => 'Unknown', 'interest' => 'Unknown',
        ])->assertRedirect($url.'#form-minat')
            ->assertSessionHasErrors(['full_name', 'phone_number', 'domicile', 'activity', 'interest', 'consent']);

        $this->get($url)->assertOk()->assertSee('Periksa kembali data Anda.')
            ->assertSee('Gunakan nomor WhatsApp Indonesia yang valid')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="not-a-phone"', false);
    }

    public function test_product_landing_page_remains_available_alongside_recruitment_page(): void
    {
        $owner = $this->subscriber();

        $this->get(route('subscriber-landing.show', $owner->landing_page_slug))
            ->assertOk()->assertSee('Kalkulator Hemat Galon');
        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()->assertSee('Form minat');
    }

    public function test_malformed_form_values_render_errors_without_breaking_the_page(): void
    {
        $owner = $this->subscriber();

        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), [
            ...$this->lead(),
            'full_name' => ['unexpected'],
            'phone_number' => ['unexpected'],
            'domicile' => ['unexpected'],
        ])->assertSessionHasErrors(['full_name', 'phone_number', 'domicile']);

        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()->assertSee('Periksa kembali data Anda.');
    }

    private function subscriber(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Nadia Putri', 'full_name' => 'Nadia Putri', 'dst_code' => 'DST001',
            'status' => 'Active', 'phone_number' => '0812-3456-7890',
        ], $attributes));
        $user->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));
        Subscription::create([
            'user_id' => $user->id, 'duration_months' => 3, 'amount' => 150000,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }

    private function lead(): array
    {
        return [
            'full_name' => 'Calon Mitra', 'phone_number' => '+62 813-2345-6789', 'domicile' => 'Jakarta Selatan',
            'activity' => 'Karyawan', 'interest' => 'Memahami skema komisi', 'consent' => '1',
        ];
    }
}
