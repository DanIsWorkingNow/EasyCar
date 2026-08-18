<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Level 1 authorization refactor (fixes TD-02, TD-16; implements FR-AUTH-02,
 * FR-AUTH-03, FR-USR-03). Creates the roles/permissions AND backfills every
 * existing user's new role from their current `userLevel` column. Does not
 * touch or remove userLevel/role, and is safe to re-run — syncPermissions()/
 * syncRoles() are idempotent.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-fleet',
            'approve-booking',
            'view-dashboard',
            'manage-users',
            'view-branch-comparison',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions); // admin gets everything

        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions(['approve-booking', 'view-dashboard']);

        Role::firstOrCreate(['name' => 'customer']);

        // Backfill: map each user's existing userLevel to the new role.
        // 0 = customer, 1 = staff, 5 = admin.
        User::query()->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->syncRoles([match ((int) $user->userLevel) {
                    5 => 'admin',
                    1 => 'staff',
                    default => 'customer',
                }]);
            }
        });
    }
}
