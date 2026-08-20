<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('returns branch-scoped KPIs for a staff user regardless of a supplied branch_id', function () {
    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create();

    $staff = User::factory()->create(['userLevel' => 1, 'branch_id' => $ownBranch->id]);
    $staff->assignRole('staff');
    $token = $staff->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/dashboard/kpis?branch_id={$otherBranch->id}"); // attempted override, must be ignored

    $response->assertOk()->assertJsonPath('meta.branch_id', $ownBranch->id);
});

it('lets admin see a global view by default and filter to one branch', function () {
    $admin = User::factory()->create(['userLevel' => 5]);
    $admin->assignRole('admin');
    $token = $admin->createToken('test')->plainTextToken;

    $default = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/dashboard/kpis');
    $default->assertOk()->assertJsonPath('meta.branch_id', null);
});

it('hides branch comparison from non-admin roles', function () {
    $staff = User::factory()->create(['userLevel' => 1]);
    $staff->assignRole('staff');
    $token = $staff->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/dashboard/branch-comparison')
        ->assertStatus(403);
});

it('denies dashboard access to a plain customer', function () {
    $customer = User::factory()->create(['userLevel' => 0]);
    $customer->assignRole('customer');
    $token = $customer->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/dashboard/kpis')
        ->assertStatus(403);
});
