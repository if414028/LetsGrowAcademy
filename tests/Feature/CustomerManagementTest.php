<?php

namespace Tests\Feature;

use App\Models\{Customer, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (array_keys(config('roles.rank')) as $role) Role::findOrCreate($role, 'web');
    }

    private function user(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['status' => 'Active']);
        $user->assignRole($role);
        return $user;
    }

    public function test_all_roles_only_list_their_own_customers_but_only_admins_can_edit(): void
    {
        $owner = $this->user('Health Planner');
        Customer::create(['full_name' => 'Other Customer', 'health_planner_id' => $owner->id]);
        foreach (['Admin', 'Head Admin', 'Sales Manager', 'Health Manager', 'Health Planner'] as $role) {
            $user = $this->user($role);
            $ownCustomer = Customer::create(['full_name' => "{$role} Customer", 'health_planner_id' => $user->id]);
            $this->actingAs($user);
            $response = $this->get(route('customers.index'))
                ->assertOk()
                ->assertSee("{$role} Customer")
                ->assertDontSee('Other Customer');
            if (in_array($role, ['Admin', 'Head Admin'])) {
                $response->assertSee('Edit');
                $this->get(route('customers.edit', $ownCustomer))->assertOk();
            } else {
                $response->assertDontSee('>Edit<', false);
                $this->get(route('customers.edit', $ownCustomer))->assertForbidden();
                $this->put(route('customers.update', $ownCustomer), ['full_name' => 'Changed'])->assertForbidden();
            }
        }
    }

    public function test_hp_can_create_multiple_customers_only_for_self(): void
    {
        $hp = $this->user('Health Planner');
        $other = $this->user('Health Planner');
        $this->actingAs($hp)->get(route('customers.create'))->assertOk();
        $data = ['full_name' => 'First', 'address' => 'Jakarta', 'health_planner_id' => $other->id];
        $this->post(route('customers.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['full_name' => 'First', 'health_planner_id' => $hp->id]);
        $data['full_name'] = 'Second';
        $this->post(route('customers.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseHas('customers', ['full_name' => 'Second', 'health_planner_id' => $hp->id]);
    }

    public function test_hm_can_create_multiple_and_sales_manager_is_read_only(): void
    {
        $hm = $this->user('Health Manager');
        $this->actingAs($hm);
        foreach (['First', 'Second'] as $name) {
            $this->post(route('customers.store'), ['full_name' => $name, 'address' => 'Jakarta'])->assertSessionHasNoErrors();
        }
        $this->assertEquals(2, Customer::where('health_planner_id', $hm->id)->count());
        $this->actingAs($this->user('Sales Manager'))->get(route('customers.create'))->assertForbidden();
        $this->post(route('customers.store'), ['full_name' => 'Third'])->assertForbidden();
    }

    public function test_inactive_hp_transfers_customer_to_nearest_hm_and_reactivation_does_not_reclaim(): void
    {
        $hm = $this->user('Health Manager');
        $middle = $this->user('Health Planner');
        $hp = $this->user('Health Planner');
        DB::table('user_hierarchies')->insert([
            ['parent_user_id' => $hm->id, 'child_user_id' => $middle->id],
            ['parent_user_id' => $middle->id, 'child_user_id' => $hp->id],
        ]);
        Customer::create(['full_name' => 'HM Customer', 'health_planner_id' => $hm->id]);
        $customer = Customer::create(['full_name' => 'HP Customer', 'health_planner_id' => $hp->id]);
        DB::transaction(fn () => $hp->update(['status' => 'Inactive']));
        $this->assertEquals($hm->id, $customer->fresh()->health_planner_id);
        $this->assertEquals(2, Customer::where('health_planner_id', $hm->id)->count());
        $hp->update(['status' => 'Active']);
        $this->assertEquals($hm->id, $customer->fresh()->health_planner_id);
    }

    public function test_admin_status_update_transfers_customer_through_user_form(): void
    {
        $hm = $this->user('Health Manager');
        $hp = $this->user('Health Planner');
        DB::table('user_hierarchies')->insert(['parent_user_id' => $hm->id, 'child_user_id' => $hp->id]);
        $customer = Customer::create(['full_name' => 'Customer', 'health_planner_id' => $hp->id]);
        $this->actingAs($this->user('Head Admin'))->put(route('users.update', $hp), [
            'name' => $hp->name, 'email' => $hp->email, 'status' => 'Inactive',
            'role' => 'Health Planner', 'referrer_user_id' => $hm->id,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Inactive', $hp->fresh()->status);
        $this->assertEquals($hm->id, $customer->fresh()->health_planner_id);
    }

    public function test_hp_with_customer_and_no_hm_cannot_be_deactivated(): void
    {
        $hp = $this->user('Health Planner');
        $customer = Customer::create(['full_name' => 'Customer', 'health_planner_id' => $hp->id]);
        try {
            DB::transaction(fn () => $hp->update(['status' => 'Inactive']));
            $this->fail('Expected missing manager validation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $this->assertSame('Active', $hp->fresh()->status);
        $this->assertEquals($hp->id, $customer->fresh()->health_planner_id);
    }

    public function test_admin_can_assign_multiple_customers_to_active_hp_but_not_inactive_hp(): void
    {
        $hp = $this->user('Health Planner');
        Customer::create(['full_name' => 'Existing', 'health_planner_id' => $hp->id]);
        $customer = Customer::create(['full_name' => 'Unassigned']);
        $this->actingAs($this->user('Admin'));
        $data = ['full_name' => 'Unassigned', 'address' => 'Jakarta', 'health_planner_id' => $hp->id];
        $this->put(route('customers.update', $customer), $data)->assertSessionHasNoErrors();
        $this->assertEquals(2, Customer::where('health_planner_id', $hp->id)->count());
        $inactive = $this->user('Health Planner');
        $inactive->update(['status' => 'Inactive']);
        $data['health_planner_id'] = $inactive->id;
        $this->put(route('customers.update', $customer), $data)->assertSessionHasErrors('health_planner_id');
        $this->assertEquals($hp->id, $customer->fresh()->health_planner_id);
    }
}
