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
            $t->unsignedBigInteger('health_manager_id')->nullable();
            $t->timestamp('deactivated_at')->nullable();
            $t->boolean('is_secondary_account')->default(false);
            $t->unsignedBigInteger('primary_account_id')->nullable();
        });
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_no')->nullable();
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

    public function test_reconciliation_identifies_unassigned_sales_without_modifying_data(): void
    {
        $this->user('Head Admin');
        $hm = $this->user('Health Manager');
        $assigned = $this->user('Health Planner', $hm);
        $unassigned = $this->user('Health Planner', null, ['status' => 'Inactive', 'name' => 'Unassigned HP']);
        $this->sale($assigned, 48);
        $this->sale($unassigned, 2, ['order_no' => 'SO-MISSING-2']);
        $this->sale($unassigned, 100, ['status' => 'dibatalkan']);
        $this->sale($unassigned, 100, ['deleted_at' => now()]);
        $this->sale($unassigned, 100, ['key_in_at' => '2026-07-01', 'install_date' => '2026-07-01']);
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });

        $this->artisan('sales:reconcile-dashboard')
            ->expectsOutputToContain('SO tidak masuk tabel: SO-MISSING-2')
            ->expectsOutputToContain('Sales: Unassigned HP (#'.$unassigned->id.') | Status akun: Inactive')
            ->expectsOutputToContain('sales tidak berada dalam cakupan hierarki HM yang ditampilkan')
            ->assertSuccessful();

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $query);
        }
        $this->assertSame('Inactive', $unassigned->fresh()->status);
        $this->assertDatabaseCount('sales_orders', 5);
    }

    public function test_reconciliation_distinguishes_inactive_hm_from_inactive_hp(): void
    {
        $admin = $this->user('Head Admin');
        $inactiveHm = $this->user('Health Manager', null, ['status' => 'Inactive', 'name' => 'Inactive HM']);
        $inactiveHp = $this->user('Health Planner', $inactiveHm, ['status' => 'Inactive']);
        $this->sale($inactiveHp, 2, ['order_no' => 'SO-INACTIVE-HM']);
        $this->artisan('sales:reconcile-dashboard', ['--user' => $admin->id])
            ->expectsOutputToContain('SO tidak masuk tabel: SO-INACTIVE-HM')
            ->expectsOutputToContain('sales tidak berada dalam cakupan hierarki HM yang ditampilkan')
            ->assertSuccessful();
    }

    public function test_reconciliation_identifies_hm_history_exclusions(): void
    {
        $this->user('Head Admin');
        $hm = $this->user('Health Manager', null, ['name' => 'Current HM']);
        $demoted = $this->user('Health Planner', $hm);
        DB::table('health_manager_periods')->insert(['user_id' => $demoted->id, 'started_on' => '2026-08-01', 'ended_on' => null]);
        $this->sale($demoted, 2, ['order_no' => 'SO-HISTORY-EXCLUDED']);
        $this->artisan('sales:reconcile-dashboard')
            ->expectsOutputToContain('SO tidak masuk tabel: SO-HISTORY-EXCLUDED')
            ->expectsOutputToContain('sales ada dalam tim HM, tetapi SO dikecualikan')
            ->expectsOutputToContain('HM kandidat: Current HM')
            ->assertSuccessful();
    }

    public function test_reconciliation_reports_duplicate_attribution_and_correct_inactive_hp_attribution(): void
    {
        $this->user('Head Admin');
        $hm = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $hm, ['status' => 'Inactive']);
        $this->sale($hp, 50);
        $this->artisan('sales:reconcile-dashboard')
            ->expectsOutputToContain('Semua SO pada card teratribusi tepat satu kali ke tabel HM.')
            ->assertSuccessful();

        // A newly promoted HM's pre-promotion sale can currently match both rows.
        $promoted = $this->user('Health Manager', $hm, ['hm_since' => '2026-10-01']);
        $this->sale($promoted, 2, ['order_no' => 'SO-DUPLICATE']);
        $this->artisan('sales:reconcile-dashboard')
            ->expectsOutputToContain('Atribusi ganda: SO-DUPLICATE | Units 2')
            ->assertSuccessful();
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
    public function test_reassigning_hp_from_promoted_sm_reconciles_48_to_50_across_screens(): void
    {
        $admin = $this->user('Head Admin');
        $hm = $this->user('Health Manager', null, ['name' => 'Original HM']);
        $target = $this->user('Health Manager', null, ['name' => 'Daniel Prasadja', 'dst_code' => 'DST230600279']);
        $former = $this->user('Sales Manager', null, ['name' => 'Gabrielle Prasadja', 'dst_code' => 'DST230500300']);
        $normal = $this->user('Health Planner', $hm, ['health_manager_id' => $hm->id]);
        $orphan = $this->user('Health Planner', $former, ['status' => 'Inactive', 'health_manager_id' => $former->id]);
        $this->sale($normal, 48);
        $this->sale($orphan, 2);
        $edges = DB::table('user_hierarchies')->orderBy('id')->get()->toArray();
        $before = app(DashboardController::class)->index($this->request($admin))->getData();
        $this->assertSame(50, $before['totalUnitsSold']);
        $this->assertSame(48, $before['healthManagerPerformance']->sum('units'));

        $this->artisan('users:transfer-health-planners', ['from' => 'DST230500300', 'to' => 'DST230600279'])
            ->expectsOutputToContain('1 HP berhasil dialihkan ke Daniel Prasadja.')->assertSuccessful();
        $orphan->refresh();
        $this->assertSame($target->id, $orphan->health_manager_id);
        $this->assertSame('Inactive', $orphan->status);
        $this->assertEquals($edges, DB::table('user_hierarchies')->orderBy('id')->get()->toArray());
        $after = app(DashboardController::class)->index($this->request($admin))->getData();
        $this->assertSame(50, $after['totalUnitsSold']);
        $this->assertSame(50, $after['healthManagerPerformance']->sum('units'));
        $this->assertSame(2, $after['healthManagerPerformance']->firstWhere('id', $target->id)->units);
        $this->assertSame(1, $after['healthManagerPerformance']->firstWhere('id', $target->id)->team_size);
        $targetDashboard = app(DashboardController::class)->index($this->request($target))->getData();
        $this->assertSame(2, $targetDashboard['totalUnitsSold']);
        $trend = app(DashboardSalesTrend::class)->build($target, 'daily');
        $this->assertSame(2, array_sum($trend['datasets'][0]['data']));
        $performance = app(PerformanceController::class)->index($this->request($target))->getData();
        $this->assertSame(2, (int) $performance['summary']->total_sudah_install);
        $this->assertSame(2, $performance['myTotalUnits']);
        $this->assertSame(2, $performance['teamPerformance']->firstWhere('id', $orphan->id)['units']);
        $export = (new ReflectionMethod(PerformanceController::class, 'buildPerformanceData'))
            ->invoke(app(PerformanceController::class), $this->request($target));
        $this->assertSame(2, (int) $export['summary']->total_sudah_install);
        $report = app(ReportController::class)->index($this->request($admin))->getData();
        $this->assertSame(2, $report['hmLeaderboard']->firstWhere('id', $target->id)['units']);
        $this->assertSame('Daniel Prasadja', $report['hpLeaderboard']->firstWhere('id', $orphan->id)['health_manager_name']);
        $recap = (new ReflectionMethod(PerformanceController::class, 'buildHealthManagerRecap'))
            ->invoke(app(PerformanceController::class), $target);
        $this->assertSame(2, $recap['months'][1]['achievement']);
        $this->assertSame(0, $recap['months'][1]['active_health_planners']);
        $planners = app(\App\Http\Controllers\SalesOrderController::class)->listHealthPlanners($this->request($admin, ['health_manager_id' => $target->id]))->getData(true);
        $this->assertSame([$orphan->id], array_column($planners, 'id'));
        $this->assertSame($target->id, app(\App\Services\CustomerOwnership::class)->nearestManager($orphan)->id);
        $this->artisan('sales:reconcile-dashboard')->expectsOutputToContain('Semua SO pada card teratribusi tepat satu kali ke tabel HM.')->assertSuccessful();
    }

    public function test_explicit_manager_reassignment_removes_ns_from_former_hm_without_double_counting(): void
    {
        $admin = $this->user('Head Admin');
        $source = $this->user('Health Manager');
        $target = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $source, ['health_manager_id' => $source->id]);
        $this->sale($hp, 7, ['install_date' => '2026-08-15', 'key_in_at' => '2026-08-15']);
        $hp->update(['health_manager_id' => $target->id]);
        $this->assertFalse($source->teamUserIds()->contains($hp->id));
        $this->assertTrue($target->teamUserIds()->contains($hp->id));
        $controller = app(ReportController::class);
        $report = $controller->index($this->request($admin, ['from' => '2026-08-01', 'to' => '2026-08-31']))->getData();
        $this->assertSame(7, $report['hmLeaderboard']->sum('units'));
        $this->assertFalse($report['hmLeaderboard']->contains('id', $source->id));
        $this->assertSame(7, $report['hmLeaderboard']->firstWhere('id', $target->id)['units']);
        $this->actingAs($target)->get(route('users.show', $hp))->assertOk();
        $this->actingAs($source)->get(route('users.show', $hp))->assertForbidden();
    }

}
