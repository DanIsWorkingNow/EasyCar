<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues a bearer token for valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

it('rejects an unauthenticated request to a protected endpoint', function () {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('returns the authenticated user via a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me');

    $response->assertOk()->assertJsonPath('data.id', $user->id);
});

it('revokes the token on logout, so it can no longer be used', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    // Sanctum's RequestGuard caches the resolved user for the lifetime of
    // the guard instance, which — inside a single test method — persists
    // across this call and the previous one. Without forgetting it, this
    // second request would still authenticate as $user via the cached
    // resolution rather than actually re-checking the (now-deleted) token,
    // even though logout genuinely revokes it in real usage (verified
    // manually against the running app: a second real HTTP request with a
    // revoked token correctly gets a 401).
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});
