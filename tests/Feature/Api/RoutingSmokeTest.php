<?php

/**
 * TD-23 regression test. Before bootstrap/app.php's ->withRouting() named
 * an api: parameter, every one of these would 404 regardless of how
 * correct the controller/route code was — there was no route registered
 * to even reach the "wrong" behavior. If this file ever starts failing,
 * check bootstrap/app.php first, not the controllers.
 */
it('actually loads routes/api.php (TD-23 regression)', function () {
    $this->getJson('/api/ping')->assertOk()->assertJsonPath('data.status', 'ok');
});

it('registers the versioned API routes, not just the ping stub', function () {
    // Unauthenticated, so this should be a 401 (route exists, auth required)
    // — NOT a 404 (route doesn't exist at all), which is what TD-23 caused.
    $this->getJson('/api/v1/bookings')->assertStatus(401);
});
