<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardInstallationsAndReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        // Skip MySQL-only DDL; add the bundle columns used by the dashboard below.
        $mysqlOnlyMigrations = [
            '2026_02_23_165638_alter_contests_description_to_text.php',
            '2026_03_19_120841_alter_target_unit_nullable_on_contests_table.php',
            '2026_07_09_000001_add_bundle_child_fields_to_sales_order_items_table.php',
        ];
        foreach (glob(database_path('migrations/*.php')) as $migration) {
            if (! in_array(basename($migration), $mysqlOnlyMigrations, true)) {
                (require $migration)->up();
            }
        }

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_item_id')->nullable();
            $table->boolean('is_cancelled')->default(false);
        });

        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($date) => substr($date ?? '', 5, 5), 2);
        foreach (['Health Planner', 'Health Manager', 'Sales Manager', 'Admin', 'Head Admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Jakarta has already reached Oct 28 while UTC is still Oct 27.
        $this->travelTo(now()->setDate(2026, 10, 28)->setTime(0, 30));
    }

    public static function roles(): array
    {
        return [
            'HP' => ['Health Planner', false],
            'HM' => ['Health Manager', false],
            'SM' => ['Sales Manager', false],
            'Admin' => ['Admin', true],
            'Head Admin' => ['Head Admin', true],
        ];
    }

    private function user(string $role, ?User $parent = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => 'Active'], $attributes));
        $user->assignRole($role);
        if ($parent) {
            DB::table('user_hierarchies')->insert(['parent_user_id' => $parent->id, 'child_user_id' => $user->id]);
        }

        return $user;
    }

    private function order(User $sales, string $number, ?string $date = '2026-10-28', string $status = 'dijadwalkan'): SalesOrder
    {
        $customer = Customer::create([
            'full_name' => 'Customer '.$number,
            'address' => 'Alamat '.$number,
            'health_planner_id' => $sales->id,
        ]);

        return SalesOrder::create([
            'order_no' => $number,
            'sales_user_id' => $sales->id,
            'customer_id' => $customer->id,
            'install_date' => $date,
            'key_in_at' => now(),
            'status' => $status,
        ]);
    }

    #[DataProvider('roles')]
    public function test_today_installations_and_read_only_details_follow_role_scope(string $role, bool $admin): void
    {
        $ancestor = $this->user('Sales Manager');
        $viewer = $this->user($role, $ancestor);
        $child = $this->user('Health Planner', $viewer);
        $grandchild = $this->user('Health Planner', $child, ['status' => 'Inactive']);
        $outside = $this->user('Health Planner', $ancestor);

        $ownOrder = $this->order($viewer, 'TODAY-SELF', status: 'selesai');
        $childOrder = $this->order($child, 'TODAY-CHILD');
        $grandchildOrder = $this->order($grandchild, 'TODAY-GRANDCHILD', status: 'ditunda');
        $outsideOrder = $this->order($outside, 'TODAY-OUTSIDE');
        $ancestorOrder = $this->order($ancestor, 'TODAY-ANCESTOR');
        $yesterday = $this->order($viewer, 'YESTERDAY', '2026-10-27');
        $tomorrow = $this->order($viewer, 'TOMORROW', '2026-10-29');
        $undated = $this->order($viewer, 'UNDATED', null);
        $deleted = $this->order($viewer, 'DELETED');
        $deleted->delete();
        // Visibility follows the SO's salesperson even after customer ownership changes.
        $childOrder->customer->update(['health_planner_id' => $outside->id]);

        $expected = collect([$ownOrder, $childOrder, $grandchildOrder]);
        if ($admin) {
            $expected = $expected->concat([$outsideOrder, $ancestorOrder]);
        }

        $response = $this->actingAs($viewer)->get(route('dashboard'))->assertOk()
            ->assertSee('Jadwal Pemasangan Hari Ini')
            ->assertSee('Order Number')->assertSee('Alamat Customer')
            ->assertDontSee('Peringatan Aktivitas Penjualan')->assertDontSee('User Nonaktif');
        $this->assertEqualsCanonicalizing($expected->pluck('id')->all(), $response->viewData('todayInstallations')->pluck('id')->all());
        foreach ($expected as $order) {
            $response->assertSee($order->order_no)->assertSee($order->customer->full_name)->assertSee($order->customer->address);
            $detail = $this->get(route('dashboard.installations.show', $order))->assertOk()
                ->assertSee($order->order_no)->assertSee('Kembali ke Overview')
                ->assertDontSee(route('sales-orders.edit', $order), false)
                ->assertDontSee('contenteditable', false);
            $document = new \DOMDocument;
            @$document->loadHTML($detail->getContent());
            $this->assertSame(0, (new \DOMXPath($document))->query('//main//form | //main//input | //main//select | //main//textarea')->length);
        }
        if (! $admin) {
            foreach ([$outsideOrder, $ancestorOrder] as $order) {
                $this->get(route('dashboard.installations.show', $order))->assertNotFound();
            }
            $this->get(route('sales-orders.edit', $ownOrder))->assertForbidden();
            $this->put(route('sales-orders.update', $ownOrder), [])->assertForbidden();
        }
        foreach ([$yesterday, $tomorrow, $undated, $deleted] as $order) {
            $this->get(route('dashboard.installations.show', $order))->assertNotFound();
        }
        // The Overview detail endpoint exposes no mutation method, including for admins.
        $this->put(route('dashboard.installations.show', $ownOrder), ['status' => 'dibatalkan'])->assertStatus(405);
        $this->assertSame('selesai', $ownOrder->fresh()->status);
    }

    public function test_installation_pagination_retains_dashboard_filters_and_return_page(): void
    {
        $viewer = $this->user('Health Planner');
        for ($i = 1; $i <= 12; $i++) {
            $this->order($viewer, sprintf('ORDER-%02d', $i));
        }

        $response = $this->actingAs($viewer)->get(route('dashboard', ['installations_page' => 2, 'trend' => 'monthly']))
            ->assertOk()->assertSee('ORDER-11')->assertSee('ORDER-12')->assertDontSee('ORDER-01');
        $paginator = $response->viewData('todayInstallations');
        $this->assertSame(12, $paginator->total());
        $this->assertCount(2, $paginator->items());
        $this->assertStringContainsString('trend=monthly', $paginator->previousPageUrl());
        $this->get(route('dashboard.installations.show', ['salesOrder' => $paginator->first(), 'installations_page' => 2, 'trend' => 'monthly']))
            ->assertOk()->assertSee(route('dashboard', ['installations_page' => 2, 'trend' => 'monthly']));
    }

    #[DataProvider('roles')]
    public function test_hp_status_contains_moved_warnings_and_inactive_users_with_existing_scope(string $role, bool $admin): void
    {
        $viewer = $this->user($role, attributes: ['created_at' => '2026-05-01']);
        $warning = $this->user('Health Planner', $viewer, ['created_at' => '2026-05-01']);
        $inactive = $this->user('Health Planner', $warning, ['status' => 'Inactive']);
        $recent = $this->user('Health Planner', $viewer, ['created_at' => '2026-10-01']);
        $outsideWarning = $this->user('Health Planner', attributes: ['created_at' => '2026-05-01']);
        $outsideInactive = $this->user('Health Planner', attributes: ['status' => 'Inactive']);

        $response = $this->actingAs($viewer)->get(route('reports.hp-status'))->assertOk()
            ->assertSee('HP Status')->assertSee('Peringatan Aktivitas Penjualan')->assertSee('User Nonaktif');
        $expectedWarnings = $admin ? [$warning->id, $outsideWarning->id] : ($role === 'Health Planner' ? [$viewer->id] : [$warning->id]);
        $expectedInactive = $admin ? [$inactive->id, $outsideInactive->id] : [$inactive->id];
        $this->assertEqualsCanonicalizing($expectedWarnings, $response->viewData('soDeactivationWarnings')->pluck('id')->all());
        $this->assertEqualsCanonicalizing($expectedInactive, $response->viewData('inactiveDownlines')->pluck('id')->all());
        $this->assertFalse($response->viewData('soDeactivationWarnings')->contains('id', $recent->id));
    }

    public function test_reports_navigation_has_two_submenus_and_old_report_is_named_leaderboards(): void
    {
        $viewer = $this->user('Admin');

        $this->actingAs($viewer)->get(route('reports.index'))->assertOk()
            ->assertSee('<h1 class="text-2xl font-semibold text-gray-900">Leaderboards</h1>', false)
            ->assertSee('desktop-reports-submenu')->assertSee('mobile-reports-submenu')
            ->assertSee(route('reports.hp-status'))->assertSee(route('reports.index'));
        $this->get(route('reports.hp-status'))->assertOk()
            ->assertSee('Tidak ada peringatan aktivitas penjualan atau user nonaktif.');
    }

    public function test_dashboard_details_and_hp_status_require_login_and_an_active_account(): void
    {
        $viewer = $this->user('Health Planner', $this->user('Health Manager'));
        $order = $this->order($viewer, 'LOGIN-REQUIRED');
        foreach ([route('dashboard'), route('dashboard.installations.show', $order), route('reports.hp-status')] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $viewer->update(['status' => 'Inactive']);
        $this->actingAs($viewer)->get(route('dashboard.installations.show', $order))->assertRedirect(route('login'));
    }
}
