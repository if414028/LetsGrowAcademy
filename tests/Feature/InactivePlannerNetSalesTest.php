<?php

namespace Tests\Feature;

use App\Http\Controllers\{DashboardController, PerformanceController, ReportController, UserController};
use App\Models\{PerformanceCutoff, User};
use App\Services\DashboardSalesTrend;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InactivePlannerNetSalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Keep reconciliation tests isolated from MySQL-only contest migrations.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['0001_01_01_000000_create_users_table.php', '2026_01_03_065641_create_permission_tables.php', '2026_01_20_005711_create_user_hierarchies_table.php', '2026_09_13_000002_create_health_manager_periods_table.php', '2026_02_24_160514_create_performance_cutoffs_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('users', function (Blueprint $t) {
            foreach (['full_name', 'dst_code', 'phone_number', 'photo'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['hm_since', 'join_date', 'date_of_birth'] as $column) {
                $t->date($column)->nullable();
            }
            $t->string('status')->default('Active');
            $t->timestamp('deactivated_at')->nullable();
            $t->boolean('is_secondary_account')->default(false);
            $t->unsignedBigInteger('primary_account_id')->nullable();
        });
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sales_user_id');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('status')->default('selesai');
            $t->string('customer_type')->default('individu');
            $t->date('install_date')->nullable();
            $t->timestamp('key_in_at')->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamp('ccp_approved_at')->nullable();
            $t->boolean('is_recurring')->default(false);
            foreach (['ccp_status', 'status_reason', 'ccp_remarks', 'payment_method_remarks'] as $column) {
                $t->string($column)->nullable();
            }
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->boolean('is_active')->default(true);
            $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('sales_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sales_order_id');
            $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('parent_item_id')->nullable();
            $t->boolean('is_cancelled')->default(false);
            $t->integer('qty');
        });
        Schema::create('bundle_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bundle_id');
            $t->integer('qty');
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->unsignedBigInteger('health_planner_id')->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
        });
        Schema::create('contests', function (Blueprint $t) {
            $t->id();
            $t->string('status');
            $t->date('start_date');
            $t->date('end_date');
            $t->timestamp('deleted_at')->nullable();
        });
        DB::table('products')->insert(['id' => 1, 'type' => 'regular']);
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format) => $format === '%m-%d' ? substr($date ?? '', 5, 5) : substr($date ?? '', 0, 7).'-01', 2);
        foreach (['Admin', 'Head Admin', 'Sales Manager', 'Health Manager', 'Health Planner'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->travelTo(now()->setDate(2026, 9, 13)->setTime(12, 0));
        PerformanceCutoff::create(['start_date' => '2026-09-01', 'end_date' => '2026-09-30']);
    }

    private function user(string $role, ?User $parent = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'hm_since' => $role === 'Health Manager' ? '2026-08-01' : null,
        ], $attributes));
        $user->assignRole($role);
        if ($parent) {
            DB::table('user_hierarchies')->insert(['parent_user_id' => $parent->id, 'child_user_id' => $user->id]);
        }

        return $user;
    }

    private function sale(User $seller, int $units, array $attributes = []): void
    {
        $id = DB::table('sales_orders')->insertGetId(array_merge([
            'sales_user_id' => $seller->id,
            'status' => 'selesai',
            'install_date' => '2026-09-13',
            'key_in_at' => '2026-09-13 09:00:00',
        ], $attributes));
        DB::table('sales_order_items')->insert(['sales_order_id' => $id, 'product_id' => 1, 'qty' => $units]);
    }

    private function request(User $user, array $parameters = []): Request
    {
        $request = Request::create('/', 'GET', $parameters);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_overview_hm_table_and_trend_keep_ns_after_hp_deactivation(): void
    {
        $admin = $this->user('Head Admin');
        $hm = $this->user('Health Manager');
        $active = $this->user('Health Planner', $hm);
        $deactivated = $this->user('Health Planner', $hm);
        $this->sale($active, 48);
        $this->sale($deactivated, 2);
        $this->sale($deactivated, 100, ['status' => 'dibatalkan']);
        $this->sale($deactivated, 100, ['deleted_at' => now()]);
        $this->sale($deactivated, 100, ['install_date' => '2026-07-01', 'key_in_at' => '2026-07-01']);

        foreach (['Active', 'Inactive'] as $status) {
            $deactivated->update(['status' => $status]);
            $data = app(DashboardController::class)->index($this->request($admin))->getData();
            $this->assertSame(50, $data['totalUnitsSold']);
            $this->assertSame(50, $data['healthManagerPerformance']->firstWhere('id', $hm->id)->units);
            $this->assertSame($status === 'Active' ? 2 : 1, $data['totalActiveHealthPlannersThisMonth']);
            foreach ([$admin, $hm] as $viewer) {
                $trend = app(DashboardSalesTrend::class)->build($viewer, 'daily');
                $this->assertSame(50, array_sum($trend['datasets'][0]['data']));
            }
        }
    }

    public function test_performance_and_export_include_inactive_planner_sales_and_member_filter(): void
    {
        $hm = $this->user('Health Manager');
        $active = $this->user('Health Planner', $hm);
        $inactive = $this->user('Health Planner', $hm, ['status' => 'Inactive']);
        $this->sale($active, 4);
        $this->sale($inactive, 6);
        $this->sale($inactive, 100, ['status' => 'dibatalkan']);
        $this->sale($inactive, 100, ['deleted_at' => now()]);
        $this->sale($inactive, 100, ['install_date' => '2026-07-01', 'key_in_at' => '2026-07-01']);
        $controller = app(PerformanceController::class);
        $exportData = new ReflectionMethod($controller, 'buildPerformanceData');

        foreach ([$this->user('Admin'), $this->user('Head Admin'), $hm] as $viewer) {
            $request = $this->request($viewer);
            $data = $controller->index($request)->getData();
            $this->assertSame(10, $data['teamPerformance']->sum('units'));
            $this->assertSame(6, $data['teamPerformance']->firstWhere('id', $inactive->id)['units']);
            if ($viewer->hasAnyRole(['Admin', 'Head Admin'])) {
                $this->assertSame(2, $data['teamMemberCount']);
            }
            $this->assertTrue($data['memberOptions']->contains('id', $inactive->id));
            $this->assertSame(10, (int) $data['summary']->total_sudah_install);
            $this->assertSame(6, (int) $data['teamSheetRows']->get($inactive->name)->firstWhere('status', 'selesai')->ns_units);
            $export = $exportData->invoke($controller, $request);
            $this->assertSame(10, (int) $export['summary']->total_sudah_install);
            $this->assertSame(6, (int) $export['teamSheetRows']->get($inactive->name)->firstWhere('status', 'selesai')->ns_units);

            $filtered = $controller->index($this->request($viewer, ['member_id' => $inactive->id]))->getData();
            $this->assertSame($inactive->id, $filtered['memberId']);
            $this->assertSame(6, $filtered['myTotalUnits']);
            $this->assertSame(6, (int) $filtered['summary']->total_sudah_install);
        }
    }

    public function test_report_personal_and_team_ns_include_inactive_primary_and_secondary_accounts(): void
    {
        $hm = $this->user('Health Manager');
        $inactive = $this->user('Health Planner', $hm, ['status' => 'Inactive']);
        $activeChild = $this->user('Health Planner', $inactive);
        $secondary = $this->user('Health Planner', $hm, [
            'status' => 'Inactive', 'is_secondary_account' => true, 'primary_account_id' => $inactive->id,
        ]);
        $this->sale($inactive, 6);
        $this->sale($activeChild, 4);
        $this->sale($secondary, 2);
        $this->sale($inactive, 100, ['status' => 'dibatalkan']);
        $this->sale($inactive, 100, ['deleted_at' => now()]);
        $controller = app(ReportController::class);

        foreach ([$this->user('Head Admin'), $hm] as $viewer) {
            foreach (['personal' => 8, 'team' => 12] as $scope => $expected) {
                $data = $controller->index($this->request($viewer, ['hp_scope' => $scope]))->getData();
                $row = $data['hpLeaderboard']->firstWhere('id', $inactive->id);
                $this->assertNotNull($row);
                $this->assertSame($expected, $row['units']);
                $this->assertSame(1, $row['active_hp']);
                $this->assertFalse($data['hpLeaderboard']->contains('id', $secondary->id));
                if ($viewer->hasRole('Head Admin')) {
                    $this->assertSame(12, $data['hmLeaderboard']->firstWhere('id', $hm->id)['units']);
                }
            }
        }
    }

    public function test_road_to_hm_counts_inactive_downline_ns_and_only_active_hp_for_headcount(): void
    {
        $owner = $this->user('Health Planner');
        $inactive = $this->user('Health Planner', $owner, ['status' => 'Inactive']);
        $activeChild = $this->user('Health Planner', $inactive);
        $this->sale($owner, 1);
        $this->sale($inactive, 6);
        $this->sale($activeChild, 4);
        $method = new ReflectionMethod(PerformanceController::class, 'buildRoadToHmData');
        $data = $method->invoke(app(PerformanceController::class), $owner);
        $rows = collect($data['rows']);
        $this->assertSame(11, $rows->firstWhere('label', 'Team')['months']['2026-09-01']['ach']);
        $this->assertSame(1, $rows->firstWhere('label', 'Personal')['months']['2026-09-01']['ach']);
        $this->assertSame(1, $rows->firstWhere('label', 'Active HP')['months']['2026-09-01']['ach']);
    }

    public function test_downline_tree_keeps_inactive_planner_and_their_descendants_with_ns(): void
    {
        $hm = $this->user('Health Manager');
        $inactive = $this->user('Health Planner', $hm, ['status' => 'Inactive']);
        $activeChild = $this->user('Health Planner', $inactive);
        $this->sale($inactive, 6);
        $this->sale($activeChild, 4);
        $method = new ReflectionMethod(UserController::class, 'buildDownlineTree');
        $tree = $method->invoke(app(UserController::class), $hm->id, '2026-09-01', '2026-09-30', false);
        $this->assertSame($inactive->id, $tree['children'][0]['id']);
        $this->assertSame(6, $tree['children'][0]['total_net_sales']);
        $this->assertSame($activeChild->id, $tree['children'][0]['children'][0]['id']);
        $this->assertSame(4, $tree['children'][0]['children'][0]['total_net_sales']);
    }
}
