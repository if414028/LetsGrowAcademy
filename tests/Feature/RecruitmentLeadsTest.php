<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\RecruitmentLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruitmentLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_form_saves_data_and_server_owned_attribution_before_confirmation(): void
    {
        $owner = $this->subscriber('Nadia Planner');
        $visitor = $this->subscriber('Hendra Manager', 'Health Manager');

        $this->actingAs($visitor)->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), [
            ...$this->form(),
            'owner_user_id' => $visitor->id,
            'user_id' => $visitor->id,
            'source' => 'another_form',
            'source_slug' => $visitor->landing_page_slug,
            'source_url' => 'https://example.test/forged-source',
        ])->assertStatus(303)
            ->assertRedirect(route('subscriber-recruitment.thank-you', $owner->landing_page_slug));

        $this->assertDatabaseCount('recruitment_leads', 1);
        $this->assertDatabaseHas('recruitment_leads', [
            'owner_user_id' => $owner->id,
            'source' => RecruitmentLead::SOURCE,
            'source_slug' => $owner->landing_page_slug,
            'source_url' => route('subscriber-recruitment.show', $owner->landing_page_slug),
            'full_name' => 'Calon Mitra Rahasia',
            'phone_number' => '6281323456789',
            'domicile' => 'Jakarta Selatan',
            'activity' => 'Karyawan',
            'interest' => 'Memahami skema komisi',
        ]);
        $this->assertNotNull(RecruitmentLead::first()->consented_at);
        $this->assertTrue(RecruitmentLead::first()->owner->is($owner));
        $this->assertDatabaseCount('customers', 0);

        $this->get(route('subscriber-recruitment.show', $owner->landing_page_slug))
            ->assertOk()->assertDontSee('Calon Mitra Rahasia');
    }

    public function test_hp_and_hm_only_see_their_own_form_entries_even_when_the_hp_is_a_downline(): void
    {
        $hm = $this->subscriber('Hendra Manager', 'Health Manager');
        $hp = $this->subscriber('Nadia Planner');
        DB::table('user_hierarchies')->insert(['parent_user_id' => $hm->id, 'child_user_id' => $hp->id]);
        $this->post(route('subscriber-recruitment.submit', $hp->landing_page_slug), $this->form(['full_name' => 'Peminat Milik HP']));
        $this->post(route('subscriber-recruitment.submit', $hm->landing_page_slug), $this->form(['full_name' => 'Peminat Milik HM']));
        Customer::create(['full_name' => 'Customer Lama', 'health_planner_id' => $hp->id]);

        $response = $this->actingAs($hm)->get(route('subscriber-recruitment.leads', ['owner_user_id' => $hp->id]))
            ->assertOk()->assertViewHas('leadCount', 1)
            ->assertSee('Peminat Milik HM')->assertDontSee('Peminat Milik HP')
            ->assertDontSee('Customer Lama')
            ->assertSee('Jakarta Selatan')->assertSee('Memahami skema komisi')
            ->assertSee('Karyawan')->assertSee('Persetujuan data diberikan')
            ->assertSee('/rekrutmen/'.$hm->landing_page_slug, false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $this->actingAs($hp)->get(route('subscriber-recruitment.leads'))
            ->assertOk()->assertViewHas('leadCount', 1)
            ->assertSee('Peminat Milik HP')->assertDontSee('Peminat Milik HM')
            ->assertDontSee('Customer Lama');
        $this->actingAs($hp)->get(route('subscriber-recruitment.index'))
            ->assertOk()->assertSee('Daftar Peminat')->assertViewHas('leadCount', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_search_never_escapes_owner_scope_and_accepts_local_phone_format(): void
    {
        $owner = $this->subscriber('Nadia');
        $other = $this->subscriber('Other Owner');
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form(['full_name' => 'Dewi Lestari']));
        $this->post(route('subscriber-recruitment.submit', $other->landing_page_slug), $this->form(['full_name' => 'Dewi Asing']));
        $this->actingAs($owner);

        foreach (['Dewi', 'Jakarta', '0813-2345-6789'] as $search) {
            $this->get(route('subscriber-recruitment.leads', ['q' => $search]))
                ->assertOk()->assertSee('Dewi Lestari')->assertDontSee('Dewi Asing')
                ->assertViewHas('leads', fn ($leads) => $leads->total() === 1);
        }
        $this->get(route('subscriber-recruitment.leads', ['q' => 'Dewi Asing']))
            ->assertOk()->assertSee('Peminat belum ditemukan')->assertDontSee('Dewi Asing</h2>', false)
            ->assertViewHas('leads', fn ($leads) => $leads->total() === 0);
    }

    public function test_lead_menu_requires_login_and_an_active_subscription(): void
    {
        $url = route('subscriber-recruitment.leads');
        $this->get($url)->assertRedirect(route('login'));
        $user = $this->subscriber('Expired Subscriber');
        $user->subscriptions()->update(['ends_at' => now()->subSecond()]);
        $this->actingAs($user)->get($url)->assertRedirect(route('subscriptions.index'));

        $user->subscriptions()->delete();
        $this->actingAs($user)->get($url)->assertRedirect(route('subscriptions.index'));
    }

    public function test_invalid_or_unavailable_forms_do_not_store_any_registration(): void
    {
        $owner = $this->subscriber('Nadia');
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form([
            'phone_number' => 'invalid', 'consent' => '0',
        ]))->assertSessionHasErrors(['phone_number', 'consent']);
        $owner->subscriptions()->update(['ends_at' => now()->subSecond()]);
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form())->assertNotFound();
        $this->assertDatabaseCount('recruitment_leads', 0);
    }

    public function test_source_snapshot_and_ownership_survive_slug_changes_and_reuse(): void
    {
        $owner = $this->subscriber('Original Owner');
        $originalSlug = $owner->landing_page_slug;
        $this->post(route('subscriber-recruitment.submit', $originalSlug), $this->form())->assertStatus(303);
        $owner->update(['name' => 'Renamed Owner']);
        $replacement = $this->subscriber('Original Owner');
        $this->assertSame($originalSlug, $replacement->landing_page_slug);

        $this->actingAs($owner)->get(route('subscriber-recruitment.leads'))
            ->assertOk()->assertSee('Calon Mitra Rahasia')
            ->assertSee('/rekrutmen/'.$originalSlug, false)->assertViewHas('leadCount', 1);
        $this->actingAs($replacement)->get(route('subscriber-recruitment.leads'))
            ->assertOk()->assertDontSee('Calon Mitra Rahasia')->assertViewHas('leadCount', 0);
        $this->assertDatabaseHas('recruitment_leads', ['owner_user_id' => $owner->id, 'source_slug' => $originalSlug]);
    }

    public function test_same_phone_can_register_through_different_owners_and_optional_interest_is_nullable(): void
    {
        $owner = $this->subscriber('Nadia');
        $other = $this->subscriber('Hendra', 'Health Manager');
        $this->post(route('subscriber-recruitment.submit', $owner->landing_page_slug), $this->form(['interest' => null]));
        $this->post(route('subscriber-recruitment.submit', $other->landing_page_slug), $this->form());

        $this->assertDatabaseCount('recruitment_leads', 2);
        $this->assertSame(1, $owner->recruitmentLeads()->count());
        $this->assertSame(1, $other->recruitmentLeads()->count());
        $this->assertNull($owner->recruitmentLeads()->first()->interest);
        $this->actingAs($owner)->get(route('subscriber-recruitment.leads'))->assertOk()->assertSee('Tidak diisi');
    }

    public function test_pagination_and_counts_only_include_registrations_from_the_owners_recruitment_form(): void
    {
        $owner = $this->subscriber('Nadia');
        $other = $this->subscriber('Other Owner');
        for ($i = 1; $i <= 17; $i++) {
            $this->savedLead($owner, ['full_name' => 'Peminat '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $this->savedLead($other, ['full_name' => 'Peminat User Lain']);
        $this->savedLead($owner, ['full_name' => 'Data Form Lain', 'source' => 'other_form']);
        $this->actingAs($owner)->get(route('subscriber-recruitment.leads'))
            ->assertOk()->assertViewHas('leadCount', 17)
            ->assertViewHas('leads', fn ($leads) => $leads->total() === 17 && $leads->count() === 15)
            ->assertSee('Peminat 17')->assertDontSee('Peminat User Lain')->assertDontSee('Data Form Lain');
        $this->actingAs($owner)->get(route('subscriber-recruitment.leads', ['page' => 2]))
            ->assertOk()->assertViewHas('leads', fn ($leads) => $leads->count() === 2)
            ->assertSee('Peminat 01')->assertDontSee('Peminat User Lain');
    }

    private function subscriber(string $name, string $role = 'Health Planner'): User
    {
        $user = User::factory()->create([
            'name' => $name, 'full_name' => $name, 'status' => 'Active',
            'phone_number' => '081234567890', 'dst_code' => 'DST001',
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $role]));
        $user->subscriptions()->create([
            'duration_months' => 3, 'amount' => 150000, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Calon Mitra Rahasia', 'phone_number' => '+62 813-2345-6789',
            'domicile' => 'Jakarta Selatan', 'activity' => 'Karyawan',
            'interest' => 'Memahami skema komisi', 'consent' => '1',
        ], $overrides);
    }

    private function savedLead(User $owner, array $overrides = []): RecruitmentLead
    {
        $values = $this->form();
        unset($values['consent']);

        return $owner->recruitmentLeads()->create(array_merge($values, [
            'phone_number' => '6281323456789', 'source' => RecruitmentLead::SOURCE,
            'source_slug' => $owner->landing_page_slug,
            'source_url' => route('subscriber-recruitment.show', $owner->landing_page_slug),
            'consented_at' => now(),
        ], $overrides));
    }
}
