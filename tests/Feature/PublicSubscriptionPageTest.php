<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicSubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_subscription_information_and_is_sent_to_login(): void
    {
        $response = $this->get(route('subscriptions.public'));

        $response->assertOk()
            ->assertSee('Subscription Sales')
            ->assertSee('Selling Kit premium')
            ->assertSee('Rp 50.000')
            ->assertSee(route('login'), false)
            ->assertSee('wa.me/628886547788', false)
            ->assertDontSee('Midtrans');
    }

    public function test_authenticated_user_subscription_cta_opens_subscription_checkout(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'Health Planner']));

        $this->actingAs($user)
            ->get(route('subscriptions.public'))
            ->assertOk()
            ->assertSee(route('subscriptions.index'), false);
    }

    public function test_admin_and_head_admin_do_not_receive_subscription_checkout_cta(): void
    {
        foreach (['Admin', 'Head Admin'] as $roleName) {
            $user = User::factory()->create();
            $user->assignRole(Role::firstOrCreate(['name' => $roleName]));

            $this->actingAs($user)
                ->get(route('subscriptions.public'))
                ->assertOk()
                ->assertDontSee('href="'.route('subscriptions.index').'"', false)
                ->assertDontSee('Pilih Paket Subscription');
        }
    }

    public function test_homepage_footer_invites_visitors_to_join_as_sales(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Daftar Jadi Sales')
            ->assertSee('Benefit Subscription')
            ->assertSee('Peluang Sales Coway')
            ->assertSee(route('subscriptions.public'), false)
            ->assertSee('wa.me/628886547788', false)
            ->assertDontSee('Akses Admin &amp; Sales', false);
    }
}
