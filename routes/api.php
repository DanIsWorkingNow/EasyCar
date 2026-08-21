<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CarController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SUPERSEDES the routes/api.php shipped in the API kit.
|
| Rate limiting (TSD Section 8.11.2), now that TD-23's fix (bootstrap/app.php)
| makes this file reachable at all:
|
|   - The whole /api/v1 group sits behind throttle:api (120/min per
|     authenticated user, 30/min per IP for guests).
|   - /auth/login additionally gets throttle:login (5/min by ip+email) —
|     unchanged from Level 3, just finally reachable.
|   - Anything that mutates data (cars/bookings/users writes, plus the CSV
|     export, which is a read but expensive enough to throttle like one)
|     additionally gets throttle:api-write (30/min), stacked on top of the
|     general 'api' budget rather than replacing it.
|
| DEVIATION FROM THE KIT: the kit's version moved /bookings/export and
| /bookings/bulk-approve down into the trailing throttle:api-write group,
| placing them AFTER /bookings/{booking} is registered. Laravel matches
| routes in registration order, so GET /bookings/export would have been
| swallowed by GET /bookings/{booking} (booking="export") instead of
| reaching BookingController::export — exactly the class of bug the
| original API kit's own comment ("/export and /bulk-approve before
| /{booking} for the same reason") was written to prevent. Restored that
| ordering here: both routes are still throttle:api-write, just registered
| before the {booking} wildcard, same as before this kit.
|
| No endpoints, controllers, or route names changed from the API kit.
*/

Route::get('/ping', fn () => response()->json(['data' => ['status' => 'ok']]))->middleware('throttle:api');

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/branches', [BranchController::class, 'index']);
        Route::get('/branches/{branch}', [BranchController::class, 'show']);

        // /available before /{car} so "available" is never matched as an id.
        Route::get('/cars/available', [CarController::class, 'available']);
        Route::get('/cars', [CarController::class, 'index']);
        Route::get('/cars/{car}', [CarController::class, 'show']);

        // /export and /bulk-approve before /{booking} — see file header.
        Route::middleware('throttle:api-write')->group(function () {
            Route::get('/bookings/export', [BookingController::class, 'export']);
            Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove']);
        });
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);

        Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('/dashboard/trend', [DashboardController::class, 'trend']);
        Route::get('/dashboard/branch-comparison', [DashboardController::class, 'branchComparison']);
        Route::get('/dashboard/pending-queue', [DashboardController::class, 'pendingQueue']);

        Route::get('/users', [UserController::class, 'index']);

        // Remaining writes — throttle:api-write stacks on top of the outer
        // throttle:api, it doesn't replace it.
        Route::middleware('throttle:api-write')->group(function () {
            Route::post('/bookings', [BookingController::class, 'store']);
            Route::put('/bookings/{booking}', [BookingController::class, 'update']);
            Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
            Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve']);
            Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject']);

            Route::post('/cars', [CarController::class, 'store']);
            Route::put('/cars/{car}', [CarController::class, 'update']);
            Route::delete('/cars/{car}', [CarController::class, 'destroy']);

            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });
});
