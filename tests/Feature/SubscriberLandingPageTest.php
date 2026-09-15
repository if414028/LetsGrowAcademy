<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscriber_has_a_public_landing_page_with_personal_whatsapp_cta(): void
    {
        $user = User::factory()->create([
            'name' => 'Nadia Putri',
            'full_name' => 'Nadia Putri',
            'dst_code' => 'DST001',
            'phone_number' => '0812-3456-7890',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 3,
            'amount' => 150000,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->get('/nadia-putri-dst001')
            ->assertOk()
            ->assertSee('Kalkulator Hemat Galon')
            ->assertSee('wa.me/6281234567890', false)
            ->assertSee('Nadia Putri');
    }

    public function test_inactive_or_expired_subscriber_landing_page_is_not_public(): void
    {
        $user = User::factory()->create([
            'name' => 'Expired Member',
            'phone_number' => '081234567890',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'duration_months' => 3,
            'amount' => 150000,
            'status' => 'active',
            'starts_at' => now()->subMonths(4),
            'ends_at' => now()->subDay(),
        ]);

        $this->get('/expired-member')->assertNotFound();
    }

    public function test_duplicate_names_receive_unique_public_slugs(): void
    {
        $first = User::factory()->create(['name' => 'Dewi Lestari', 'dst_code' => 'DST010']);
        $second = User::factory()->create(['name' => 'Dewi Lestari', 'dst_code' => 'DST010']);

        $this->assertSame('dewi-lestari-dst010', $first->landing_page_slug);
        $this->assertSame('dewi-lestari-dst010-2', $second->landing_page_slug);
    }

    public function test_public_slug_uses_user_name_and_dst_code_instead_of_full_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Herlina',
            'full_name' => 'Herlina Mariana Pardede',
            'dst_code' => 'D01234',
        ]);

        $this->assertSame('herlina-d01234', $user->landing_page_slug);
    }

    public function test_landing_page_menu_is_only_visible_to_active_subscribers(): void
    {
        $subscriber = User::factory()->create([
            'name' => 'Active Member',
            'phone_number' => '081234567890',
            'status' => 'Active',
        ]);
        $nonSubscriber = User::factory()->create(['status' => 'Active']);

        Subscription::create([
            'user_id' => $subscriber->id,
            'duration_months' => 3,
            'amount' => 150000,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($subscriber)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('Landing Page Produk')
            ->assertSee(route('subscriber-landing.index'), false);

        $this->actingAs($nonSubscriber)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertDontSee('Landing Page Produk');

        $this->actingAs($subscriber)
            ->get(route('subscriber-landing.index'))
            ->assertOk()
            ->assertSee('Tampilan Landing Page Produk')
            ->assertSee('<iframe', false)
            ->assertSee(route('subscriber-landing.show', $subscriber->landing_page_slug), false)
            ->assertSee('Lihat Landing Page');

        $this->actingAs($nonSubscriber)
            ->get(route('subscriber-landing.index'))
            ->assertRedirect(route('subscriptions.index'));
    }
}
