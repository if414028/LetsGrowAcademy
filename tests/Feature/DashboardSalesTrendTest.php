<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardSalesTrend;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardSalesTrendTest extends TestCase
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
            $t->unsignedBigInteger('health_manager_id')->nullable();
            $t->timestamp('deactivated_at')->nullable();
            $t->boolean('is_secondary_account')->default(false);
            $t->unsignedBigInteger('primary_account_id')->nullable();
        });
        (require database_path('migrations/2026_09_13_000002_create_health_manager_periods_table.php'))->up();
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sales_user_id');
            $t->date('install_date');
            $t->timestamp('key_in_at')->nullable();
            $t->integer('units');
            $t->string('status')->default('selesai');
            $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('type');
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
        DB::table('products')->insert(['id' => 1, 'type' => 'regular']);
        Role::findOrCreate('Health Manager', 'web');
        Role::findOrCreate('Health Planner', 'web');
        $this->travelTo(now()->setDate(2026, 10, 2)->setTime(12, 0));
        foreach (['Sales Manager', 'Admin', 'Head Admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
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

    private function sale(User $seller, int $qty, string $date = '2026-10-02', string $status = 'selesai', bool $deleted = false): int
    {
        $id = DB::table('sales_orders')->insertGetId([
            'sales_user_id' => $seller->id, 'install_date' => $date,
            'key_in_at' => $date.' 10:00:00', 'units' => $qty, 'status' => $status,
            'deleted_at' => $deleted ? $date.' 11:00:00' : null,
        ]);
        DB::table('sales_order_items')->insert(['sales_order_id' => $id, 'product_id' => 1, 'qty' => $qty]);

        return $id;
    }

    public function test_sm_sees_each_hm_including_nested_hm_without_double_counting(): void
    {
        $sm = $this->user('Sales Manager');
        $hm = $this->user('Health Manager', $sm, ['name' => 'A HM']);
        $hp = $this->user('Health Planner', $hm);
        $nested = $this->user('Health Manager', $hm, ['name' => 'B HM']);
        $nestedHp = $this->user('Health Planner', $nested);
        $outside = $this->user('Health Manager', null, ['name' => 'Outside']);
        $this->user('Health Manager', $sm, ['status' => 'Inactive']);
        foreach ([[$hm, 2], [$hp, 3], [$nested, 7], [$nestedHp, 11], [$outside, 100], [$sm, 50]] as [$seller, $qty]) {
            $this->sale($seller, $qty);
        }
        $this->sale($hp, 200, status: 'pending');
        $this->sale($hp, 300, deleted: true);
        $parent = $this->sale($hp, 4);
        DB::table('sales_order_items')->insert(['sales_order_id' => $parent, 'product_id' => 1, 'qty' => 4, 'parent_item_id' => 1]);

        $result = app(DashboardSalesTrend::class)->build($sm, 'daily');
        $this->assertTrue($result['perHealthManager']);
        $this->assertCount(30, $result['labels']);
        $this->assertSame(['A HM', 'B HM'], array_column($result['datasets'], 'label'));
        $this->assertSame(9, $result['datasets'][0]['data'][29]);
        $this->assertSame(18, $result['datasets'][1]['data'][29]);
        $this->assertSame(0, $result['datasets'][0]['data'][0]);
    }

    public function test_admin_and_head_admin_see_all_active_hms_outside_their_hierarchy(): void
    {
        $hm = $this->user('Health Manager', null, ['name' => 'HM One']);
        $otherHm = $this->user('Health Manager', null, ['name' => 'HM Two']);
        $this->sale($hm, 5);
        $this->sale($otherHm, 9);
        foreach (['Admin', 'Head Admin'] as $role) {
            $result = app(DashboardSalesTrend::class)->build($this->user($role), 'weekly');
            $this->assertCount(2, $result['datasets']);
            $this->assertSame([5, 9], array_map(fn ($series) => array_sum($series['data']), $result['datasets']));
        }
    }

    public function test_daily_for_hm_and_hp_preserves_their_sales_scope(): void
    {
        $hm = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $hm);
        $outside = $this->user('Health Planner');
        $this->sale($hm, 2);
        $this->sale($hp, 3);
        $this->sale($outside, 100);
        foreach ([[$hm, 5], [$hp, 3]] as [$user, $expected]) {
            $result = app(DashboardSalesTrend::class)->build($user, 'daily');
            $this->assertFalse($result['perHealthManager']);
            $this->assertCount(1, $result['datasets']);
            $this->assertSame($expected, $result['datasets'][0]['data'][29]);
        }
    }

    public function test_period_boundaries_zero_fill_and_invalid_filter(): void
    {
        $hp = $this->user('Health Planner');
        foreach ([['2026-09-02', 100], ['2026-09-03', 2], ['2026-09-27', 3], ['2026-09-28', 5], ['2026-10-02', 7]] as [$date, $qty]) {
            $this->sale($hp, $qty, $date);
        }
        $service = app(DashboardSalesTrend::class);
        $daily = $service->build($hp, 'daily');
        $this->assertSame('03 Sep', $daily['labels'][0]);
        $this->assertSame(17, array_sum($daily['datasets'][0]['data']));
        $weekly = $service->build($hp, 'weekly');
        $this->assertCount(8, $weekly['labels']);
        $this->assertSame(3, $weekly['datasets'][0]['data'][6]);
        $this->assertSame(12, $weekly['datasets'][0]['data'][7]);
        $monthly = $service->build($hp, 'monthly');
        $this->assertCount(6, $monthly['labels']);
        $this->assertSame([0, 0, 0, 0, 110, 7], $monthly['datasets'][0]['data']);
        $this->assertSame('weekly', $service->build($hp, 'invalid')['trend']);
    }
}
