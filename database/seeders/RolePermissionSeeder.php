<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Permissions List
        $userPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',

            'view user hierarchy',
            'manage user hierarchy',

            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            'assign roles',
            'assign permissions',
        ];

        $salesPermissions = [
            'view sales performance',
            'view sales orders',
            'create sales orders',
            'edit sales orders',
            'cancel sales orders',

            'view customers',
            'create customers',
            'edit customers',

            'view products',
            'create products',
            'edit products',
            'deactivate products',

            'view sales kpi',
        ];

        $installationPermissions = [
            'mark installation complete',
            'view installation status',

            'approve ccp',
            'reject ccp',
        ];

        $contestPermissions = [
            'view contest',
            'create contest',
            'edit contest',
            'delete contest',

            'publish contest',
            'close contest',

            'join contest',
            'view contest progress',

            'calculate contest winner',
            'view contest winner',
        ];

        $systemPermissions = [
            'view dashboard',
            'view reports',
            'export reports',
        ];

        $permissions = array_merge(
            $userPermissions,
            $salesPermissions,
            $installationPermissions,
            $contestPermissions,
            $systemPermissions
        );

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        //Roles
        $headAdmin = Role::firstOrCreate(['name' => 'Head Admin']);
        $admin     = Role::firstOrCreate(['name' => 'Admin']);
        $sm        = Role::firstOrCreate(['name' => 'Sales Manager']);
        $hm        = Role::firstOrCreate(['name' => 'Health Manager']);
        $hp        = Role::firstOrCreate(['name' => 'Health Planner']);

        //Assign permissions role
        //Head Admin: all (superuser)
        $headAdmin->syncPermissions(Permission::all());

        //Admin: limited
        $admin->syncPermissions([
            // Create new user
            'create users',

            // Edit all user
            'edit users',

            // Management Produk
            'view products',
            'create products',
            'edit products',
            'deactivate products',

            // Management Sales Order
            'view sales orders',
            'create sales orders',
            'edit sales orders',
            'cancel sales orders',
        ]);

        // SM
        $sm->syncPermissions([
            'view dashboard',
            'view reports',
            'export reports',

            'view users',
            'view user hierarchy',

            'view sales performance',
            'view sales orders',
            'view customers',
            'view products',
            'view sales kpi',

            'view contest',
            'create contest',
            'edit contest',
            'publish contest',
            'close contest',
            'view contest progress',
            'view contest winner',
        ]);

        // HM
        $hm->syncPermissions([
            'view dashboard',

            'view users',
            'view user hierarchy',

            'view sales performance',
            'view sales orders',
            'view customers',
            'view products',
            'view sales kpi',

            'view installation status',
            'approve ccp',
            'reject ccp',

            'view contest',
            'join contest',
            'view contest progress',
            'view contest winner',
        ]);

        // HP
        $hp->syncPermissions([
            'view dashboard',

            'view sales orders',
            'create sales orders',
            'view customers',
            'create customers',
            'view products',

            'mark installation complete',
            'view installation status',

            'view contest',
            'join contest',
            'view contest progress',
        ]);

        // ===== Admin user =====
        $adminUser = User::updateOrCreate(
            ['email' => 'letsgrowacademydev@gmail.com'],
            [
                'name' => 'Lets Grow Academy Admin',
                'password' => bcrypt('letsgrowacademy2025'),
            ]
        );

        $adminUser->syncRoles(['Admin']);


        // ===== Head Admin user =====
        $headAdminUser = User::updateOrCreate(
            ['email' => 'letsgrowacademy.head@letsgrowacademy.id'], 
            [
                'name' => 'Lets Grow Academy Head Admin',
                'password' => bcrypt('letsgrowacademy.head'),
            ]
        );

        $headAdminUser->syncRoles(['Head Admin']);
    }
}
