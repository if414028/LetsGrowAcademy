<?php

namespace Tests\Feature;

use App\Models\{Customer, SalesOrder, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlannerDeactivationTest extends TestCase
{
    use RefreshDatabase;

    private function planner(string $created = '2026-03-17 18:00:00', string $role = 'Health Planner'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['status' => 'Active', 'created_at' => $created]);
        $user->assignRole($role);
        return $user;
    }

    public function test_due_date_is_inclusive_and_overdue_accounts_are_processed_idempotently(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->startOfDay());
        $due = $this->planner();
        $overdue = $this->planner('2026-02-01');
        $future = $this->planner('2026-03-18');
        $manager = $this->planner('2026-01-01', 'Health Manager');
        $this->artisan('users:deactivate-inactive-planners')->assertSuccessful();
        $this->assertSame('Inactive', $due->fresh()->status);
        $this->assertSame('Inactive', $overdue->fresh()->status);
        $this->assertSame('Active', $future->fresh()->status);
        $this->assertSame('Active', $manager->fresh()->status);
        $this->artisan('users:deactivate-inactive-planners')->expectsOutput('Deactivated: 0; blocked: 0')->assertSuccessful();
    }

    public function test_only_completed_non_deleted_installations_extend_the_deadline(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->startOfDay());
        foreach (['selesai', 'pending', 'deleted', 'undated'] as $index => $status) {
            $hp = $this->planner();
            $customer = Customer::create(['full_name' => 'Test']);
            $order = SalesOrder::create(['order_no' => 'TEST-'.$index, 'sales_user_id' => $hp->id,
                'customer_id' => $customer->id, 'status' => in_array($status, ['deleted', 'undated']) ? 'selesai' : $status,
                'install_date' => $status === 'undated' ? null : '2026-09-16']);
            if ($status === 'deleted') $order->delete();
            $this->artisan('users:deactivate-inactive-planners')->assertSuccessful();
            $this->assertSame($status === 'selesai' ? 'Active' : 'Inactive', $hp->fresh()->status);
        }
    }

    public function test_customer_transfer_and_missing_manager_do_not_interrupt_other_users(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->startOfDay());
        $blocked = $this->planner();
        Customer::create(['full_name' => 'Blocked', 'health_planner_id' => $blocked->id]);
        $hp = $this->planner();
        $hm = $this->planner('2026-01-01', 'Health Manager');
        DB::table('user_hierarchies')->insert(['parent_user_id' => $hm->id, 'child_user_id' => $hp->id]);
        $customer = Customer::create(['full_name' => 'Transfer', 'health_planner_id' => $hp->id]);
        $this->artisan('users:deactivate-inactive-planners')->assertFailed();
        $this->assertSame('Active', $blocked->fresh()->status);
        $this->assertSame('Inactive', $hp->fresh()->status);
        $this->assertEquals($hm->id, $customer->fresh()->health_planner_id);
    }
}
