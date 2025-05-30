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

Route::get('/', function () {
    return view('welcome');
});

//Authentication Routes
Auth::routes();

// Home & Public Cars
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/cars', [PublicCarController::class, 'index'])->name('cars.index');

// Customer Bookings (authenticated users only)
Route::middleware(['auth'])->group(function () {
    Route::resource('bookings', BookingController::class);
    Route::get('/bookings/available-cars', [BookingController::class, 'getAvailableCars']);
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('/cars', AdminCarController::class)->names([
        'index' => 'admin.cars.index',
        'create' => 'admin.cars.create',
        'store' => 'admin.cars.store',
        'show' => 'admin.cars.show',
        'edit' => 'admin.cars.edit',
        'update' => 'admin.cars.update',
        'destroy' => 'admin.cars.destroy'
    ]);
    Route::resource('/bookings', AdminBookingController::class)->names([
        'index' => 'admin.bookings.index',
        'create' => 'admin.bookings.create',
        'store' => 'admin.bookings.store',
        'show' => 'admin.bookings.show',
        'edit' => 'admin.bookings.edit',
        'update' => 'admin.bookings.update',
        'destroy' => 'admin.bookings.destroy'
    ]);
 
    // Additional booking routes - KEEPING ORIGINAL NAMES FOR COMPATIBILITY
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
        'destroy' => 'admin.users.destroy'
    ]);


});

// STAFF ROUTES
Route::middleware(['auth', 'staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::resource('/cars', StaffCarController::class)->names([
        'index' => 'staff.cars.index',
        'create' => 'staff.cars.create',
        'store' => 'staff.cars.store',
        'show' => 'staff.cars.show',
        'edit' => 'staff.cars.edit',
        'update' => 'staff.cars.update',
        'destroy' => 'staff.cars.destroy'
    ]);
    Route::resource('/bookings', StaffBookingController::class)->names([
        'index' => 'staff.bookings.index',
        'create' => 'staff.bookings.create',
        'store' => 'staff.bookings.store',
        'show' => 'staff.bookings.show',
        'edit' => 'staff.bookings.edit',
        'update' => 'staff.bookings.update',
        'destroy' => 'staff.bookings.destroy'
    ]);
});

Route::get('/test-admin', function () {
    return 'Admin middleware works!';
})->middleware(['auth', 'admin']);


Route::post('admin/bookings/{id}/approve', [AdminBookingController::class, 'approve'])->name('admin.bookings.approve');
Route::post('admin/bookings/{id}/reject', [AdminBookingController::class, 'reject'])->name('admin.bookings.reject');



