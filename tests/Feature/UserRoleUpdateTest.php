<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use the user migrations without unrelated MySQL-only contest migrations.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_01_03_065641_create_permission_tables.php',
            '2026_01_03_090948_create_contests_table.php',
            '2026_01_03_091001_create_contest_participants_table.php',
            '2026_01_20_005711_create_user_hierarchies_table.php',
            '2026_01_20_012112_add_profile_fields_to_users_table.php',
            '2026_02_10_072111_add_deleted_at_to_contests_table.php',
            '2026_09_02_000001_add_hm_since_to_users_table.php',
            '2026_09_13_000001_add_deactivated_at_to_users_table.php',
            '2026_09_13_000002_create_health_manager_periods_table.php',
            '2026_09_18_000001_add_secondary_account_fields_to_users_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        foreach (array_keys(config('roles.rank')) as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function user(string $role, ?User $parent = null): User
    {
        $user = User::factory()->create(['status' => 'Active']);
        $user->assignRole($role);

        if ($parent) {
            DB::table('user_hierarchies')->insert([
                'parent_user_id' => $parent->id,
                'child_user_id' => $user->id,
                'relation_type' => 'referral',
            ]);
        }

        return $user;
    }

    private function payload(User $user, User $referrer): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'Active',
            'role' => 'Health Manager',
            'referrer_user_id' => $referrer->id,
        ];
    }

    public function test_promotion_under_hp_referrer_preserves_the_entire_referral_tree(): void
    {
        foreach (['Admin', 'Head Admin'] as $actorRole) {
            $admin = $this->user($actorRole);
            $manager = $this->user('Health Manager');
            $referrer = $this->user('Health Planner', $manager);
            $user = $this->user('Health Planner', $referrer);
            $downline = $this->user('Health Planner', $user);
            $this->user('Health Planner', $downline);
            $this->user('Health Planner', $referrer);
            $edgesBefore = DB::table('user_hierarchies')->orderBy('id')
                ->get(['parent_user_id', 'child_user_id', 'relation_type'])->toArray();

            $this->actingAs($admin)
                ->put(route('users.update', $user), $this->payload($user, $referrer))
                ->assertRedirect(route('users.show', $user))
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');

            $user->refresh();
            $this->assertSame(['Health Manager'], $user->getRoleNames()->all());
            $this->assertTrue($referrer->fresh()->hasRole('Health Planner'));
            $this->assertSame(today()->toDateString(), $user->hm_since->toDateString());
            $this->assertDatabaseHas('health_manager_periods', [
                'user_id' => $user->id,
                'started_on' => today()->toDateString(),
                'ended_on' => null,
            ]);
            $this->assertEquals($edgesBefore, DB::table('user_hierarchies')->orderBy('id')
                ->get(['parent_user_id', 'child_user_id', 'relation_type'])->toArray());

            // A later profile edit must not reset the promotion date or reject the same referrer.
            $start = $user->hm_since->toDateString();
            $this->travel(1)->days();
            $payload = $this->payload($user, $referrer);
            $payload['name'] = 'Updated Manager';
            $this->put(route('users.update', $user), $payload)->assertSessionHasNoErrors();
            $this->assertSame('Updated Manager', $user->fresh()->name);
            $this->assertSame($start, $user->fresh()->hm_since->toDateString());
            $this->assertSame(1, DB::table('health_manager_periods')->where('user_id', $user->id)->count());
            $this->travelBack();
        }
    }

    public function test_other_roles_above_hp_referrer_are_still_rejected_without_partial_updates(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Planner');
        $user = $this->user('Health Planner', $referrer);

        foreach (['Sales Manager', 'Admin', 'Head Admin'] as $role) {
            $payload = $this->payload($user, $referrer);
            $payload['role'] = $role;
            $payload['name'] = 'Must not be saved';
            $this->actingAs($admin)->put(route('users.update', $user), $payload)
                ->assertSessionHasErrors('referrer_user_id')
                ->assertSessionMissing('success');
            $this->assertSame(['Health Planner'], $user->fresh()->getRoleNames()->all());
            $this->assertNotSame($payload['name'], $user->fresh()->name);
        }
    }

    public function test_planner_cannot_promote_themselves(): void
    {
        $referrer = $this->user('Health Planner');
        $user = $this->user('Health Planner', $referrer);

        $this->actingAs($user)->put(route('users.update', $user), $this->payload($user, $referrer))
            ->assertSessionHasNoErrors();

        $this->assertSame(['Health Planner'], $user->fresh()->getRoleNames()->all());
        $this->assertDatabaseCount('health_manager_periods', 0);
    }

    public function test_promotion_still_rejects_self_and_downline_referrers(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Planner');
        $user = $this->user('Health Planner', $referrer);
        $downline = $this->user('Health Planner', $user);

        foreach ([$user, $downline] as $invalidReferrer) {
            $this->actingAs($admin)->put(route('users.update', $user), $this->payload($user, $invalidReferrer))
                ->assertSessionHasErrors('referrer_user_id')
                ->assertSessionMissing('success');
        }

        $this->assertSame(['Health Planner'], $user->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('user_hierarchies', [
            'parent_user_id' => $referrer->id,
            'child_user_id' => $user->id,
        ]);
    }

    public function test_admin_promotion_with_eligible_referrer_saves_role_and_hm_history(): void
    {
        foreach (['Admin', 'Head Admin'] as $actorRole) {
            $admin = $this->user($actorRole);

            foreach (['Health Manager', 'Sales Manager'] as $referrerRole) {
                $referrer = $this->user($referrerRole);
                $user = $this->user('Health Planner', $referrer);
                $downline = $this->user('Health Planner', $user);

                $this->actingAs($admin)
                    ->put(route('users.update', $user), $this->payload($user, $referrer))
                    ->assertRedirect(route('users.show', $user))
                    ->assertSessionHasNoErrors()
                    ->assertSessionHas('success');

                $user->refresh();
                $this->assertTrue($user->hasRole('Health Manager'));
                $this->assertFalse($user->hasRole('Health Planner'));
                $this->assertSame(today()->toDateString(), $user->hm_since->toDateString());
                $this->assertDatabaseHas('health_manager_periods', [
                    'user_id' => $user->id,
                    'started_on' => today()->toDateString(),
                    'ended_on' => null,
                ]);
                $this->assertDatabaseHas('user_hierarchies', [
                    'parent_user_id' => $user->id,
                    'child_user_id' => $downline->id,
                ]);
            }
        }
    }

    public function test_create_does_not_silently_replace_hm_with_hp(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Planner');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Manager',
            'email' => 'new.manager@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Health Manager',
            'referrer_user_id' => $referrer->id,
        ])->assertSessionHasErrors('role')->assertSessionMissing('success');

        $this->assertDatabaseMissing('users', ['email' => 'new.manager@example.com']);
    }

    public function test_bulk_upload_reports_invalid_hm_role_instead_of_creating_hp(): void
    {
        $admin = $this->user('Head Admin');
        $referrer = $this->user('Health Planner');
        $csv = "name,email,password,role,referrer_email\nNew Manager,bulk.manager@example.com,Password123!,Health Manager,{$referrer->email}\n";

        $this->actingAs($admin)->post(route('users.bulk.store'), [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ])->assertSessionHas('bulk_success', [])
            ->assertSessionHas('bulk_failed', fn ($failures) => count($failures) === 1
                && str_contains($failures[0]['errors'][0], 'Health Planner'));

        $this->assertDatabaseMissing('users', ['email' => 'bulk.manager@example.com']);
    }
}
