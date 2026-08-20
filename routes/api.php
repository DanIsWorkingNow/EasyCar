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
| API Routes — implements TSD Section 8 in full, except the Level 4
| payment/webhook endpoints (Section 8.8), which stay out per the
| Project Owner's instruction that payments are built last.
|--------------------------------------------------------------------------
*/

Route::get('/ping', fn () => response()->json(['data' => ['status' => 'ok']]));

Route::prefix('v1')->group(function () {
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
        Route::post('/cars', [CarController::class, 'store']);
        Route::put('/cars/{car}', [CarController::class, 'update']);
        Route::delete('/cars/{car}', [CarController::class, 'destroy']);

        // /export and /bulk-approve before /{booking} for the same reason.
        Route::get('/bookings/export', [BookingController::class, 'export']);
        Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove']);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::put('/bookings/{booking}', [BookingController::class, 'update']);
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
        Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve']);
        Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject']);

        Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('/dashboard/trend', [DashboardController::class, 'trend']);
        Route::get('/dashboard/branch-comparison', [DashboardController::class, 'branchComparison']);
        Route::get('/dashboard/pending-queue', [DashboardController::class, 'pendingQueue']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
