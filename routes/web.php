<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\DriverVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Ops\BoardController as OpsBoardController;
use App\Http\Controllers\Ops\ChargeController as OpsChargeController;
use App\Http\Controllers\Ops\HandoverController as OpsHandoverController;
use App\Http\Controllers\Ops\VerificationController as OpsVerificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\CarController as StaffCarController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Ordering matters: Laravel matches in registration order, so literal
| segments (/bookings/available-cars, /admin/bookings/export) must be
| registered BEFORE the resource routes whose {wildcard} would otherwise
| swallow them. Both of those routes were unreachable before this revamp
| for exactly that reason.
|
*/

// ── Public ────────────────────────────────────────────────────────────
Route::get('/', LandingController::class)->name('landing');
Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
Route::get('/cars/{car}', [CarController::class, 'show'])->name('cars.show');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

Auth::routes();

// Gateway → server notifications (CSRF-exempt, signature-verified).
Route::post('/payments/webhook/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:120,1')
    ->where('gateway', '[a-z0-9_-]+')
    ->name('payments.webhook');

// ── Signed-in customers ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/bookings/available-cars', [BookingController::class, 'getAvailableCars'])->name('bookings.available-cars');
    Route::resource('bookings', BookingController::class);

    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('bookings.pay');
    Route::post('/bookings/{booking}/charges/pay', [PaymentController::class, 'payCharges'])
        ->middleware('throttle:10,1')
        ->name('bookings.charges.pay');
    Route::get('/payments/{payment}/return', [PaymentController::class, 'return'])->name('payments.return');
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status'])->name('payments.status');
    Route::get('/payments/{payment}/sandbox', [PaymentController::class, 'sandbox'])->name('payments.sandbox.show');
    Route::post('/payments/{payment}/sandbox', [PaymentController::class, 'completeSandbox'])->name('payments.sandbox.complete');

    Route::get('/account/verification', [DriverVerificationController::class, 'show'])->name('verification.show');
    Route::post('/account/verification', [DriverVerificationController::class, 'store'])->middleware('throttle:10,1')->name('verification.store');
    Route::get('/verification/{profile}/documents/{document}', [DriverVerificationController::class, 'document'])->name('verification.document');

    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Handover reports (customer, branch staff, admins — see VehicleInspectionPolicy).
    Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->name('inspections.show');
    Route::get('/inspections/{inspection}/photos/{photo}', [InspectionController::class, 'photo'])->name('inspections.photo');
    Route::get('/inspections/{inspection}/signature', [InspectionController::class, 'signature'])->name('inspections.signature');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// ── Rental operations (admins: every branch; staff: their own) ────────
Route::middleware(['auth', 'role:admin|staff'])->prefix('ops')->name('ops.')->group(function () {
    Route::get('/', [OpsBoardController::class, 'index'])->name('board');
    Route::get('/bookings/{booking}/cars/{car}/pickup', [OpsHandoverController::class, 'pickup'])->name('pickup');
    Route::get('/bookings/{booking}/cars/{car}/return', [OpsHandoverController::class, 'return'])->name('return');
    Route::post('/bookings/{booking}/charges', [OpsChargeController::class, 'store'])->name('charges.store');
    Route::post('/bookings/{booking}/charges/collect', [OpsChargeController::class, 'collect'])->name('charges.collect');
    Route::post('/charges/{charge}/void', [OpsChargeController::class, 'void'])->name('charges.void');

    Route::get('/verifications', [OpsVerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{profile}', [OpsVerificationController::class, 'show'])->name('verifications.show');
    Route::put('/verifications/{profile}', [OpsVerificationController::class, 'update'])->name('verifications.update');
});

// ── Admin console ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::resource('cars', AdminCarController::class)->names('admin.cars');
    Route::resource('branches', AdminBranchController::class)->except('show')->names('admin.branches');
    Route::resource('users', AdminUserController::class)->names('admin.users');

    // Literal booking routes before the {booking} resource routes.
    Route::get('/bookings/export', [AdminBookingController::class, 'export'])->name('bookings.export');
    Route::post('/bookings/bulk-approve', [AdminBookingController::class, 'bulkApprove'])->name('bookings.bulk-approve');
    Route::resource('bookings', AdminBookingController::class)->only(['index', 'show'])->names('admin.bookings');
    Route::patch('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
    Route::patch('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');

    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('admin.payments.show');
    Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('admin.payments.refund');
    Route::post('/payments/{payment}/mark-refunded', [AdminPaymentController::class, 'markRefunded'])->name('admin.payments.mark-refunded');
});

// ── Staff console (scoped to the staff member's branch) ───────────────
Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/cars', [StaffCarController::class, 'index'])->name('staff.cars.index');

    Route::post('/bookings/bulk-approve', [StaffBookingController::class, 'bulkApprove'])->name('staff.bookings.bulk-approve');
    Route::get('/bookings', [StaffBookingController::class, 'index'])->name('staff.bookings.index');
    Route::get('/bookings/{booking}', [StaffBookingController::class, 'show'])->name('staff.bookings.show');
    Route::patch('/bookings/{booking}/approve', [StaffBookingController::class, 'approve'])->name('staff.bookings.approve');
    Route::patch('/bookings/{booking}/reject', [StaffBookingController::class, 'reject'])->name('staff.bookings.reject');
});
