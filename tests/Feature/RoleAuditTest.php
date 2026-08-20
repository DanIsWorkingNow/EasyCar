<?php

use App\Models\Branch;
use App\Models\RoleAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('assigns the matching Spatie role when an admin creates a new staff user', function () {
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');
    $branch = Branch::factory()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Staff',
        'email' => 'staff@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'userLevel' => 1,
        'branch_id' => $branch->id,
    ]);

    $newUser = User::where('email', 'staff@example.com')->first();

    expect($newUser->hasRole('staff'))->toBeTrue();
    expect(RoleAuditLog::where('user_id', $newUser->id)->where('new_role', 'staff')->exists())->toBeTrue();
});

it('re-syncs the Spatie role and logs the change when userLevel is edited', function () {
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');

    $staff = User::factory()->create(['userLevel' => 1]);
    $staff->assignRole('staff');

    $this->actingAs($admin)->put(route('admin.users.update', $staff), [
        'name' => $staff->name,
        'email' => $staff->email,
        'userLevel' => 5, // promote to admin
    ]);

    $staff->refresh();

    expect($staff->hasRole('admin'))->toBeTrue();
    expect($staff->hasRole('staff'))->toBeFalse();

    $log = RoleAuditLog::where('user_id', $staff->id)->latest()->first();
    expect($log->old_role)->toBe('staff');
    expect($log->new_role)->toBe('admin');
    expect($log->changed_by)->toBe($admin->id);
});

it('does not write an audit log entry when the role does not change', function () {
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');

    $staff = User::factory()->create(['userLevel' => 1]);
    $staff->assignRole('staff');

    $this->actingAs($admin)->put(route('admin.users.update', $staff), [
        'name' => 'Updated Name Only',
        'email' => $staff->email,
        'userLevel' => 1, // unchanged
    ]);

    expect(RoleAuditLog::where('user_id', $staff->id)->count())->toBe(0);
});
