<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('login');
});

it('throttles login attempts after 5 per minute (unchanged from Level 3, now actually reachable)', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertStatus(429);
});

it('applies the general api limiter to authenticated requests', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Just confirm the limiter headers are present and sane — hitting the
    // full 120/min ceiling in a test is unnecessary and slow; the limiter
    // being wired up at all is what TD-23 blocked, which is what matters here.
    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');

    $response->assertOk();
    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
});

it('throttles write endpoints more strictly than the general api limit', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['userLevel' => 5]);
    $user->assignRole('admin');
    $token = $user->createToken('test')->plainTextToken;

    // 30/min is the api-write ceiling; 31st call in the same minute should 429.
    for ($i = 0; $i < 30; $i++) {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/bookings/bulk-approve', ['booking_ids' => []]);
    }

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/bookings/bulk-approve', ['booking_ids' => []])
        ->assertStatus(429);
});
