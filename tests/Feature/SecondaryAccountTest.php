<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecondaryAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (array_keys(config('roles.rank')) as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => 'Active'], $attributes));
        $user->assignRole($role);

        return $user;
    }

    private function payload(User $referrer, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Secondary User',
            'email' => 'secondary@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Health Planner',
            'referrer_user_id' => $referrer->id,
            'status' => 'Active',
        ], $overrides);
    }

    private function completedOrder(User $salesUser, int $quantity, string $suffix): void
    {
        $now = now();
        $customerId = DB::table('customers')->insertGetId([
            'full_name' => "Customer {$suffix}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productId = DB::table('products')->insertGetId([
            'sku' => "SKU-{$suffix}",
            'product_name' => "Product {$suffix}",
            'is_active' => true,
            'type' => 'regular',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $salesOrderId = DB::table('sales_orders')->insertGetId([
            'order_no' => "SO-{$suffix}",
            'sales_user_id' => $salesUser->id,
            'customer_id' => $customerId,
            'status' => 'selesai',
            'install_date' => '2026-09-15',
            'key_in_at' => '2026-09-10 09:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('sales_order_items')->insert([
            'sales_order_id' => $salesOrderId,
            'product_id' => $productId,
            'qty' => $quantity,
            'is_cancelled' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_admin_can_create_secondary_account_with_hp_primary_account(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Manager');
        $primary = $this->user('Health Planner');

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload($referrer, [
                'is_secondary_account' => '1',
                'primary_account_id' => $primary->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $secondary = User::where('email', 'secondary@example.com')->firstOrFail();

        $this->assertTrue($secondary->is_secondary_account);
        $this->assertTrue($secondary->primaryAccount->is($primary));
        $this->assertTrue($primary->secondaryAccounts->contains($secondary));
    }

    public function test_primary_account_is_required_for_secondary_account(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Manager');

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload($referrer, [
                'is_secondary_account' => '1',
            ]))
            ->assertSessionHasErrors('primary_account_id');

        $this->assertDatabaseMissing('users', ['email' => 'secondary@example.com']);
    }

    public function test_primary_account_must_be_a_non_secondary_hp_or_hm(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Manager');
        $salesManager = $this->user('Sales Manager');
        $secondaryHp = $this->user('Health Planner', ['is_secondary_account' => true]);

        foreach ([$salesManager, $secondaryHp] as $invalidPrimary) {
            $this->actingAs($admin)
                ->post(route('users.store'), $this->payload($referrer, [
                    'is_secondary_account' => '1',
                    'primary_account_id' => $invalidPrimary->id,
                ]))
                ->assertSessionHasErrors('primary_account_id');
        }
    }

    public function test_primary_account_search_only_returns_primary_hp_and_hm_accounts(): void
    {
        $admin = $this->user('Head Admin');
        $hp = $this->user('Health Planner', ['name' => 'Eligible HP']);
        $hm = $this->user('Health Manager', ['name' => 'Eligible HM']);
        $this->user('Sales Manager', ['name' => 'Hidden SM']);
        $this->user('Health Planner', [
            'name' => 'Hidden Secondary HP',
            'is_secondary_account' => true,
            'primary_account_id' => $hp->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('users.primary-accounts.search'))
            ->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($hp->id));
        $this->assertTrue($ids->contains($hm->id));
        $this->assertCount(2, $ids);
    }

    public function test_secondary_user_detail_displays_its_primary_account(): void
    {
        $admin = $this->user('Head Admin');
        $primary = $this->user('Health Manager', [
            'name' => 'Primary HM Name',
            'email' => 'primary.hm@example.com',
        ]);
        $secondary = $this->user('Health Planner', [
            'is_secondary_account' => true,
            'primary_account_id' => $primary->id,
        ]);

        $this->actingAs($admin)
            ->get(route('users.show', $secondary))
            ->assertOk()
            ->assertSee('Primary Account')
            ->assertSee('Primary HM Name')
            ->assertSee('primary.hm@example.com');
    }

    public function test_hp_leaderboard_switches_between_account_family_and_team_ns(): void
    {
        $admin = $this->user('Head Admin');
        $primary = $this->user('Health Planner', ['name' => 'Primary HP']);
        $secondary = $this->user('Health Planner', [
            'name' => 'Secondary HP',
            'is_secondary_account' => true,
            'primary_account_id' => $primary->id,
        ]);
        $downline = $this->user('Health Planner', ['name' => 'Downline HP']);
        $downlineSecondary = $this->user('Health Planner', [
            'name' => 'Downline Secondary HP',
            'is_secondary_account' => true,
            'primary_account_id' => $downline->id,
        ]);

        DB::table('user_hierarchies')->insert([
            'parent_user_id' => $primary->id,
            'child_user_id' => $downline->id,
            'relation_type' => 'referral',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->completedOrder($primary, 1, 'PRIMARY');
        $this->completedOrder($secondary, 2, 'SECONDARY');
        $this->completedOrder($downline, 3, 'DOWNLINE');
        $this->completedOrder($downlineSecondary, 4, 'DOWNLINE-SECONDARY');

        $personalResponse = $this->actingAs($admin)->get(route('reports.index', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'hp_scope' => 'personal',
        ]))->assertOk()->assertSee('NS Pribadi')->assertDontSee('Secondary HP</td>', false);

        $personalRow = $personalResponse->viewData('hpLeaderboard')->firstWhere('id', $primary->id);
        $this->assertSame(3, $personalRow['units']);
        $this->assertSame(1, $personalRow['active_hp']);

        $teamResponse = $this->actingAs($admin)->get(route('reports.index', [
            'from' => '2026-09-01',
            'to' => '2026-09-30',
            'hp_scope' => 'team',
        ]))->assertOk()->assertSee('NS Team');

        $teamRow = $teamResponse->viewData('hpLeaderboard')->firstWhere('id', $primary->id);
        $this->assertSame(10, $teamRow['units']);
        $this->assertSame(2, $teamRow['active_hp']);
    }
}
