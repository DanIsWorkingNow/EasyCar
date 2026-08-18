<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController as PublicCarController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Staff\CarController as StaffCarController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;

/*
|--------------------------------------------------------------------------
| CUTOVER (TSD 4.4 step 3) — changes from the pre-cutover routes/web.php:
|
| 1. ['auth','admin'] / ['auth','staff'] -> ['auth','role:admin'] /
|    ['auth','role:staff']. 'role:' is registered automatically by
|    spatie/laravel-permission — no manual alias needed, which is why
|    AdminMiddleware/StaffMiddleware were deleted.
|
| 2. Removed the stray debug route (`/test-admin`) and the two duplicate
|    legacy POST /admin/bookings/{id}/approve|reject routes that used to
|    sit at the bottom of this file — they resolved to the same action as
|    the PATCH routes already inside the admin group, just with a
|    different verb and a different route name (admin.bookings.approve vs
|    bookings.approve), which was confusing leftover routing rather than a
|    second real feature.
|
| 3. Staff approve/reject/bulk-approve routes point at Staff\BookingController
|    (TD-20 fix), scoped to the staff member's own branch inside the
|    controller.
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/cars', [PublicCarController::class, 'index'])->name('cars.index');

// Customer Bookings (authenticated users only)
Route::middleware(['auth'])->group(function () {
    Route::resource('bookings', BookingController::class);
    Route::get('/bookings/available-cars', [BookingController::class, 'getAvailableCars']);
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::resource('/cars', AdminCarController::class)->names([
        'index' => 'admin.cars.index',
        'create' => 'admin.cars.create',
        'store' => 'admin.cars.store',
        'show' => 'admin.cars.show',
        'edit' => 'admin.cars.edit',
        'update' => 'admin.cars.update',
        'destroy' => 'admin.cars.destroy',
    ]);

    Route::resource('/bookings', AdminBookingController::class)->names([
        'index' => 'admin.bookings.index',
        'create' => 'admin.bookings.create',
        'store' => 'admin.bookings.store',
        'show' => 'admin.bookings.show',
        'edit' => 'admin.bookings.edit',
        'update' => 'admin.bookings.update',
        'destroy' => 'admin.bookings.destroy',
    ]);

    Route::get('/bookings/export', [AdminBookingController::class, 'export'])->name('bookings.export');
    Route::post('/bookings/bulk-approve', [AdminBookingController::class, 'bulkApprove'])->name('bookings.bulk-approve');
    Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
    Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');

    Route::resource('/users', AdminUserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
});

// Staff Routes
Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');

    Route::resource('/cars', StaffCarController::class)->only(['index'])->names([
        'index' => 'staff.cars.index',
    ]);

    Route::get('/bookings', [StaffBookingController::class, 'index'])->name('staff.bookings.index');
    Route::get('/bookings/{booking}', [StaffBookingController::class, 'show'])->name('staff.bookings.show');
    Route::patch('/bookings/{booking}/approve', [StaffBookingController::class, 'approve'])->name('staff.bookings.approve');
    Route::patch('/bookings/{booking}/reject', [StaffBookingController::class, 'reject'])->name('staff.bookings.reject');
    Route::post('/bookings/bulk-approve', [StaffBookingController::class, 'bulkApprove'])->name('staff.bookings.bulk-approve');
});
