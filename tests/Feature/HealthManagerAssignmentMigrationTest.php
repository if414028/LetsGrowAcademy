<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HealthManagerAssignmentMigrationTest extends TestCase
{
    public function test_migration_snapshots_nearest_current_hm_including_inactive_hp_and_never_guesses_sm(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['0001_01_01_000000_create_users_table.php', '2026_01_03_065641_create_permission_tables.php', '2026_01_20_005711_create_user_hierarchies_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('Active');
        });
        $users = [];
        foreach (['Health Manager', 'Health Planner', 'Health Planner', 'Sales Manager', 'Health Planner', 'Health Manager', 'Health Planner'] as $role) {
            Role::findOrCreate($role, 'web');
            // Avoid status-change observers; this test starts from legacy data.
            $user = User::withoutEvents(fn () => User::factory()->create());
            $user->assignRole($role);
            $users[] = $user;
        }
        [$hm, $hp, $inactive, $sm, $orphan, $nestedHm, $nestedHp] = $users;
        DB::table('users')->where('id', $inactive->id)->update(['status' => 'Inactive']);
        foreach ([[$hm, $hp], [$hp, $inactive], [$sm, $orphan], [$hm, $nestedHm], [$nestedHm, $nestedHp]] as [$parent, $child]) {
            DB::table('user_hierarchies')->insert(['parent_user_id' => $parent->id, 'child_user_id' => $child->id]);
        }
        $edges = DB::table('user_hierarchies')->orderBy('id')->get()->toArray();
        $migration = require database_path('migrations/2026_10_06_000001_add_health_manager_id_to_users_table.php');
        $migration->up();
        foreach ([$hp, $inactive] as $planner) $this->assertSame($hm->id, $planner->fresh()->health_manager_id);
        $this->assertSame($nestedHm->id, $nestedHp->fresh()->health_manager_id);
        $this->assertNull($orphan->fresh()->health_manager_id);
        $this->assertNull($hm->fresh()->health_manager_id);
        $this->assertSame('Inactive', $inactive->fresh()->status);
        $this->assertEquals($edges, DB::table('user_hierarchies')->orderBy('id')->get()->toArray());
        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'health_manager_id'));
        $this->assertEquals($edges, DB::table('user_hierarchies')->orderBy('id')->get()->toArray());
    }
}
