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
            '2026_10_06_000001_add_health_manager_id_to_users_table.php',
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
    public function test_hp_creation_saves_manager_from_referrer_or_explicit_assignment(): void
    {
        $admin = $this->user('Head Admin');
        $hm = $this->user('Health Manager');
        $otherHm = $this->user('Health Manager');
        $referrer = $this->user('Health Planner', $hm);
        $referrer->update(['health_manager_id' => $otherHm->id]);
        foreach ([null, $hm->id] as $index => $explicit) {
            $data = ['name' => 'New Planner', 'email' => 'assigned'.$index.'@example.com',
                'password' => 'Password123!', 'password_confirmation' => 'Password123!',
                'role' => 'Health Planner', 'referrer_user_id' => $referrer->id];
            if ($explicit) $data['health_manager_id'] = $explicit;
            $this->actingAs($admin)->post(route('users.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
            $planner = User::where('email', $data['email'])->firstOrFail();
            $this->assertSame($explicit ?? $otherHm->id, $planner->health_manager_id);
            $this->assertDatabaseHas('user_hierarchies', ['parent_user_id' => $referrer->id, 'child_user_id' => $planner->id]);
        }
        $this->get(route('users.create'))->assertOk()->assertSee('health_manager_id', false);
    }

    public function test_promoting_hm_to_sm_requires_and_atomically_transfers_their_planners(): void
    {
        $admin = $this->user('Head Admin');
        $sm = $this->user('Sales Manager');
        $hm = $this->user('Health Manager', $sm);
        $hm->update(['hm_since' => '2026-01-01']);
        \App\Services\HealthManagerPeriods::sync($hm, false);
        $target = $this->user('Health Manager', $sm);
        $hp = $this->user('Health Planner', $hm);
        $hp->update(['health_manager_id' => $hm->id]);
        $inactive = $this->user('Health Planner', $hp);
        // Query update avoids unrelated customer-transfer events in this user-only fixture.
        User::whereKey($inactive->id)->update(['status' => 'Inactive', 'health_manager_id' => $hm->id]);
        $nested = $this->user('Health Manager', $hm);
        $nestedHp = $this->user('Health Planner', $nested);
        $nestedHp->update(['health_manager_id' => $nested->id]);
        $edges = DB::table('user_hierarchies')->orderBy('id')->get()->toArray();
        $payload = array_merge($this->payload($hm, $sm), ['role' => 'Sales Manager']);

        $this->actingAs($admin)->put(route('users.update', $hm), $payload)
            ->assertSessionHasErrors('replacement_health_manager_id');
        $this->assertTrue($hm->fresh()->hasRole('Health Manager'));
        $this->assertSame($hm->id, $hp->fresh()->health_manager_id);
        $this->assertDatabaseHas('health_manager_periods', ['user_id' => $hm->id, 'ended_on' => null]);

        $this->get(route('users.edit', $hm))->assertOk()->assertSee('HM pengganti saat promosi menjadi SM');
        $this->put(route('users.update', $hm), $payload + ['replacement_health_manager_id' => $target->id])
            ->assertSessionHasNoErrors()->assertRedirect(route('users.show', $hm));
        $this->assertTrue($hm->fresh()->hasRole('Sales Manager'));
        foreach ([$hp, $inactive] as $planner) $this->assertSame($target->id, $planner->fresh()->health_manager_id);
        $this->assertSame('Inactive', $inactive->fresh()->status);
        $this->assertSame($nested->id, $nestedHp->fresh()->health_manager_id);
        $this->assertEquals($edges, DB::table('user_hierarchies')->orderBy('id')->get()->toArray());
    }

    public function test_admin_can_reassign_hp_without_changing_referrer_and_self_cannot(): void
    {
        $admin = $this->user('Head Admin');
        $old = $this->user('Health Manager');
        $new = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $old);
        $hp->update(['health_manager_id' => $old->id]);
        $payload = array_merge($this->payload($hp, $old), ['role' => 'Health Planner', 'health_manager_id' => $new->id]);
        $this->actingAs($admin)->put(route('users.update', $hp), $payload)->assertSessionHasNoErrors();
        $this->assertSame($new->id, $hp->fresh()->health_manager_id);
        $this->assertDatabaseHas('user_hierarchies', ['parent_user_id' => $old->id, 'child_user_id' => $hp->id]);
        $this->actingAs($hp)->put(route('users.update', $hp), ['health_manager_id' => $old->id])->assertSessionHasNoErrors();
        $this->assertSame($new->id, $hp->fresh()->health_manager_id);
        $this->post(route('users.transfer-health-planners', $new), ['health_manager_id' => $old->id])->assertForbidden();
    }

    public function test_bulk_transfer_from_already_promoted_sm_handles_unassigned_hp_and_preserves_nested_hm(): void
    {
        $admin = $this->user('Head Admin');
        $former = $this->user('Sales Manager');
        $target = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $former);
        $nested = $this->user('Health Manager', $former);
        $nestedHp = $this->user('Health Planner', $nested);
        $this->actingAs($admin)->post(route('users.transfer-health-planners', $former), ['health_manager_id' => $target->id])
            ->assertSessionHasNoErrors()->assertRedirect(route('users.show', $former));
        $this->assertSame($target->id, $hp->fresh()->health_manager_id);
        $this->assertNull($nestedHp->fresh()->health_manager_id);
        $this->assertDatabaseHas('user_hierarchies', ['parent_user_id' => $former->id, 'child_user_id' => $hp->id]);
    }

    public function test_transfer_rejects_non_manager_inactive_manager_and_self(): void
    {
        $admin = $this->user('Head Admin');
        $source = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $source);
        $inactive = $this->user('Health Manager');
        User::whereKey($inactive->id)->update(['status' => 'Inactive']);
        foreach ([$hp, $inactive, $source] as $target) {
            $this->actingAs($admin)->post(route('users.transfer-health-planners', $source), ['health_manager_id' => $target->id])
                ->assertSessionHasErrors('health_manager_id');
            $this->assertNull($hp->fresh()->health_manager_id);
        }
    }

    public function test_hp_promotion_keeps_their_planner_team_attached_to_new_hm(): void
    {
        $admin = $this->user('Head Admin');
        $hm = $this->user('Health Manager');
        $hp = $this->user('Health Planner', $hm);
        $hp->update(['health_manager_id' => $hm->id]);
        $child = $this->user('Health Planner', $hp);
        $child->update(['health_manager_id' => $hm->id]);
        $this->actingAs($admin)->put(route('users.update', $hp), $this->payload($hp, $hm))->assertSessionHasNoErrors();
        $this->assertNull($hp->fresh()->health_manager_id);
        $this->assertSame($hp->id, $child->fresh()->health_manager_id);
        $this->assertDatabaseHas('user_hierarchies', ['parent_user_id' => $hp->id, 'child_user_id' => $child->id]);
    }

    public function test_transfer_command_previews_then_moves_former_hm_team_and_is_safe_to_repeat(): void
    {
        $source = $this->user('Sales Manager');
        $source->update(['name' => 'Gabrielle Prasadja', 'dst_code' => 'DST230500300']);
        $destination = $this->user('Health Manager');
        $destination->update(['name' => 'Daniel Prasadja', 'dst_code' => 'DST-230600279']);
        $assigned = $this->user('Health Planner');
        $assigned->update(['health_manager_id' => $source->id]);
        $orphan = $this->user('Health Planner', $source);
        $inactive = $this->user('Health Planner', $orphan);
        User::whereKey($inactive->id)->update(['status' => 'Inactive']);
        $nested = $this->user('Health Manager', $source);
        $nestedHp = $this->user('Health Planner', $nested);
        $alreadyMoved = $this->user('Health Planner', $source);
        $alreadyMoved->update(['health_manager_id' => $nested->id]);
        $edges = DB::table('user_hierarchies')->orderBy('id')->get()->toArray();
        $users = DB::table('users')->orderBy('id')->get()->toArray();
        $arguments = ['from' => 'DST230500300', 'to' => 'DST230600279'];

        $this->artisan('users:transfer-health-planners', $arguments + ['--dry-run' => true])
            ->expectsOutputToContain('Gabrielle Prasadja')
            ->expectsOutputToContain('Daniel Prasadja')
            ->expectsOutputToContain('DRY-RUN: 3 HP akan dialihkan')
            ->assertSuccessful();
        $this->assertEquals($users, DB::table('users')->orderBy('id')->get()->toArray());

        $this->artisan('users:transfer-health-planners', $arguments)
            ->expectsOutputToContain('3 HP berhasil dialihkan ke Daniel Prasadja.')->assertSuccessful();
        foreach ([$assigned, $orphan, $inactive] as $hp) $this->assertSame($destination->id, $hp->fresh()->health_manager_id);
        $this->assertFalse($source->teamUserIds()->contains($orphan->id));
        $this->assertTrue($destination->teamUserIds()->contains($orphan->id));
        $this->assertSame('Inactive', $inactive->fresh()->status);
        $this->assertNull($nestedHp->fresh()->health_manager_id);
        $this->assertSame($nested->id, $alreadyMoved->fresh()->health_manager_id);
        $this->assertEquals($edges, DB::table('user_hierarchies')->orderBy('id')->get()->toArray());
        $this->artisan('users:transfer-health-planners', $arguments)
            ->expectsOutputToContain('0 HP berhasil dialihkan')->assertSuccessful();
    }

    public function test_transfer_command_rejects_invalid_source_destination_and_identity_without_writing(): void
    {
        $source = $this->user('Sales Manager');
        $hp = $this->user('Health Planner', $source);
        $hm = $this->user('Health Manager');
        $inactive = $this->user('Health Manager');
        User::whereKey($inactive->id)->update(['status' => 'Inactive']);
        foreach ([[$source->id, $hp->id], [$source->id, $inactive->id], [$hm->id, $hm->id], [$hp->id, $hm->id], ['DST999999999', $hm->id]] as [$from, $to]) {
            $this->artisan('users:transfer-health-planners', ['from' => (string) $from, 'to' => (string) $to])->assertFailed();
            $this->assertNull($hp->fresh()->health_manager_id);
        }

        // Canonical and hyphenated DST codes can coexist in legacy data: never guess.
        $source->update(['dst_code' => 'DST230500300']);
        $duplicate = $this->user('Sales Manager');
        $duplicate->update(['dst_code' => 'DST-230500300']);
        $this->artisan('users:transfer-health-planners', ['from' => 'DST230500300', 'to' => $hm->email])
            ->expectsOutputToContain('harus cocok tepat satu user')->assertFailed();
        $this->assertNull($hp->fresh()->health_manager_id);
    }

}
