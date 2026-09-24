<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\{HealthManagerNsScope, HealthManagerPeriods};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HealthManagerHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolate these integration tests from unrelated MySQL-only contest migrations.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['0001_01_01_000000_create_users_table.php', '2026_01_03_065641_create_permission_tables.php', '2026_01_20_005711_create_user_hierarchies_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('users', function (Blueprint $t) {
            $t->string('full_name')->nullable();
            $t->date('hm_since')->nullable();
            $t->date('join_date')->nullable();
            $t->string('dst_code')->nullable();
            $t->string('status')->default('Active');
            $t->timestamp('deactivated_at')->nullable();
            $t->boolean('is_secondary_account')->default(false);
            $t->unsignedBigInteger('primary_account_id')->nullable();
        });
        (require database_path('migrations/2026_09_13_000002_create_health_manager_periods_table.php'))->up();
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('sales_user_id'); $t->date('install_date'); $t->timestamp('key_in_at')->nullable(); $t->integer('units');
            $t->string('status')->default('selesai'); $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->string('type'); });
        Schema::create('sales_order_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('sales_order_id'); $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('parent_item_id')->nullable(); $t->boolean('is_cancelled')->default(false); $t->integer('qty');
        });
        Schema::create('bundle_items', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('bundle_id'); $t->integer('qty'); });
        DB::table('products')->insert(['id' => 1, 'type' => 'regular']);
        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format) => substr($date, 0, 7).'-01', 2);
        Role::findOrCreate('Health Manager', 'web');
        Role::findOrCreate('Health Planner', 'web');
        $this->travelTo(now()->setDate(2026, 9, 13));
    }

    private function user(string $role, ?User $parent = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'hm_since' => $role === 'Health Manager' ? '2026-08-01' : null,
        ], $attributes));
        $user->assignRole($role);
        if ($parent) DB::table('user_hierarchies')->insert(['parent_user_id' => $parent->id, 'child_user_id' => $user->id]);
        return $user;
    }

    public function test_september_demotion_preserves_august_and_repomotion_preserves_both(): void
    {
        $myra = $this->user('Health Manager');
        $mona = $this->user('Health Manager', $myra);
        $hp = $this->user('Health Planner', $mona);
        $other = $this->user('Health Planner');
        HealthManagerPeriods::sync($mona, false);
        foreach (['2026-08-15', '2026-09-15', '2026-10-15'] as $date) {
            foreach ([[$myra, 52], [$mona, 5], [$hp, 4], [$other, 100]] as [$user, $units]) {
                $orderId = DB::table('sales_orders')->insertGetId(['sales_user_id' => $user->id, 'install_date' => $date, 'units' => $units]);
                DB::table('sales_order_items')->insert(['sales_order_id' => $orderId, 'product_id' => 1, 'qty' => $units]);
            }
        }
        $mona->syncRoles(['Health Planner']);
        HealthManagerPeriods::sync($mona, true);
        $this->assertSame(52, $this->units($myra, '2026-08-15'));
        $this->assertSame(61, $this->units($myra, '2026-09-15'));
        $method = new \ReflectionMethod(\App\Http\Controllers\PerformanceController::class, 'buildHealthManagerRecap');
        $recap = $method->invoke(app(\App\Http\Controllers\PerformanceController::class), $myra);
        $this->assertSame(52, $recap['months'][0]['achievement']);
        $this->assertSame(0, $recap['months'][0]['active_health_planners']);
        $this->assertSame(61, $recap['months'][1]['achievement']);
        $this->assertSame(2, $recap['months'][1]['active_health_planners']);
        $scope = HealthManagerNsScope::resolve($myra);
        $this->assertTrue(HealthManagerNsScope::excluded($scope['exclusion_periods'][$hp->id], '2026-08-01'));
        $this->assertFalse(HealthManagerNsScope::excluded($scope['exclusion_periods'][$hp->id], '2026-09-01'));
        $this->travelTo(now()->setDate(2026, 10, 1));
        $mona->syncRoles(['Health Manager']);
        $mona->update(['hm_since' => '2026-10-01']);
        HealthManagerPeriods::sync($mona, false);
        $this->assertSame(52, $this->units($myra, '2026-08-15'));
        $this->assertSame(61, $this->units($myra, '2026-09-15'));
        $this->assertSame(52, $this->units($myra, '2026-10-15'));
    }

    private function units(User $hm, string $date): int
    {
        $q = DB::table('sales_orders as so')->where('install_date', $date);
        HealthManagerNsScope::apply($q, $hm, 'so', 'so.install_date');
        return (int) $q->sum('units');
    }

    public function test_report_leaderboards_use_install_date_before_name_to_break_a_tie(): void
    {
        $laterHm = $this->user('Health Manager', null, ['name' => 'Alpha HM']);
        $earlierHm = $this->user('Health Manager', null, ['name' => 'Zulu HM']);
        $laterHp = $this->user('Health Planner', null, ['name' => 'Alpha HP']);
        $earlierHp = $this->user('Health Planner', null, ['name' => 'Zulu HP']);

        foreach ([
            [$laterHm, '2026-09-20'],
            [$earlierHm, '2026-09-15'],
            [$laterHp, '2026-09-20'],
            [$earlierHp, '2026-09-15'],
        ] as [$seller, $installDate]) {
            $orderId = DB::table('sales_orders')->insertGetId([
                'sales_user_id' => $seller->id,
                'install_date' => $installDate,
                'key_in_at' => '2026-09-10 09:00:00',
                'units' => 5,
            ]);
            DB::table('sales_order_items')->insert([
                'sales_order_id' => $orderId,
                'product_id' => 1,
                'qty' => 5,
            ]);
        }

        $controller = app(\App\Http\Controllers\ReportController::class);
        $hmMethod = new \ReflectionMethod($controller, 'buildLeaderboardWithDescendants');
        $hpMethod = new \ReflectionMethod($controller, 'buildHealthPlannerLeaderboard');

        $hmLeaderboard = $hmMethod->invoke(
            $controller,
            collect([$laterHm, $earlierHm]),
            '2026-09-01',
            '2026-09-30'
        );
        $hpLeaderboard = $hpMethod->invoke(
            $controller,
            collect([$laterHp, $earlierHp]),
            '2026-09-01',
            '2026-09-30'
        );

        $this->assertSame([$earlierHm->id, $laterHm->id], $hmLeaderboard->pluck('id')->all());
        $this->assertSame([$earlierHp->id, $laterHp->id], $hpLeaderboard->pluck('id')->all());
    }

    public function test_inactive_hp_with_ns_is_not_counted_as_active_in_hm_recap(): void
    {
        $hm = $this->user('Health Manager');
        $activeHp = $this->user('Health Planner', $hm);
        $inactiveHp = $this->user('Health Planner', $hm, ['status' => 'Inactive']);

        foreach ([[$activeHp, 4], [$inactiveHp, 6]] as [$seller, $units]) {
            $orderId = DB::table('sales_orders')->insertGetId([
                'sales_user_id' => $seller->id,
                'install_date' => '2026-09-15',
                'units' => $units,
            ]);
            DB::table('sales_order_items')->insert([
                'sales_order_id' => $orderId,
                'product_id' => 1,
                'qty' => $units,
            ]);
        }

        $method = new \ReflectionMethod(\App\Http\Controllers\PerformanceController::class, 'buildHealthManagerRecap');
        $recap = $method->invoke(app(\App\Http\Controllers\PerformanceController::class), $hm);

        $this->assertSame(10, $recap['months'][1]['achievement']);
        $this->assertSame(1, $recap['months'][1]['active_health_planners']);
    }

    public function test_inactive_hp_with_ns_is_not_counted_as_active_in_road_to_hm(): void
    {
        $owner = $this->user('Health Planner');
        $activeHp = $this->user('Health Planner', $owner);
        $inactiveHp = $this->user('Health Planner', $owner, ['status' => 'Inactive']);

        foreach ([[$activeHp, 4], [$inactiveHp, 6]] as [$seller, $units]) {
            $orderId = DB::table('sales_orders')->insertGetId([
                'sales_user_id' => $seller->id,
                'install_date' => '2026-09-15',
                'units' => $units,
            ]);
            DB::table('sales_order_items')->insert([
                'sales_order_id' => $orderId,
                'product_id' => 1,
                'qty' => $units,
            ]);
        }

        $method = new \ReflectionMethod(\App\Http\Controllers\PerformanceController::class, 'buildRoadToHmData');
        $roadToHm = $method->invoke(app(\App\Http\Controllers\PerformanceController::class), $owner);
        $activeHpRow = collect($roadToHm['rows'])->firstWhere('label', 'Active HP');

        $this->assertSame(1, $activeHpRow['months']['2026-09-01']['ach']);
    }

    public function test_nested_hm_remains_excluded_after_parent_demotion(): void
    {
        $top = $this->user('Health Manager');
        $middle = $this->user('Health Manager', $top);
        $bottom = $this->user('Health Manager', $middle);
        $hp = $this->user('Health Planner', $bottom);
        HealthManagerPeriods::sync($middle, false);
        HealthManagerPeriods::sync($bottom, false);
        $middle->syncRoles(['Health Planner']);
        HealthManagerPeriods::sync($middle, true);
        $scope = HealthManagerNsScope::resolve($top);
        $this->assertFalse(HealthManagerNsScope::excluded($scope['exclusion_periods'][$middle->id], '2026-09-01'));
        $this->assertTrue(HealthManagerNsScope::excluded($scope['exclusion_periods'][$hp->id], '2026-09-01'));
    }

    public function test_legacy_command_is_idempotent_and_rejects_overlapping_history(): void
    {
        $mona = $this->user('Health Planner');
        $mona->update(['hm_since' => '2026-03-01']);
        $args = ['user' => (string) $mona->id, '--ended-on' => '2026-09-01'];
        $this->artisan('users:record-hm-period', $args)->assertSuccessful();
        $this->artisan('users:record-hm-period', $args)->assertSuccessful();
        $this->assertDatabaseCount('health_manager_periods', 1);
        $this->artisan('users:record-hm-period', $args + ['--started-on' => '2026-04-01'])->assertFailed();
        $this->assertDatabaseCount('health_manager_periods', 1);
    }

    public function test_same_day_role_changes_and_invalid_legacy_dates(): void
    {
        $hm = $this->user('Health Manager');
        $hm->update(['hm_since' => today()->toDateString()]);
        HealthManagerPeriods::sync($hm, false);
        $hm->syncRoles(['Health Planner']);
        HealthManagerPeriods::sync($hm, true);
        $hm->syncRoles(['Health Manager']);
        HealthManagerPeriods::sync($hm, false);
        $this->assertDatabaseCount('health_manager_periods', 1);
        $this->assertDatabaseHas('health_manager_periods', ['user_id' => $hm->id, 'ended_on' => null]);
        $this->artisan('users:record-hm-period', ['user' => (string) $hm->id, '--ended-on' => '2026-02-30'])->assertFailed();
        $this->assertDatabaseCount('health_manager_periods', 1);
    }
}
