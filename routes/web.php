<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarController as PublicCarController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\CarController as AdminCarController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// Home & Public Cars
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/cars', [PublicCarController::class, 'index'])->name('cars.index');

// Bookings (authenticated users only)
Route::middleware(['auth'])->group(function () {
    Route::resource('bookings', BookingController::class)->only(['create', 'store', 'index']);
});

// Admin Routes

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.cars.index');
    });

    Route::resource('cars', AdminCarController::class);
});

Route::resource('bookings', BookingController::class);



