<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->branch = Branch::factory()->create(['name' => 'Shah Alam', 'address' => '1 Jalan Test']);
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'type' => 'SUV', 'is_featured' => true, 'brand' => 'Nissan', 'model' => 'X-Trail']);
    Car::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->customer = User::factory()->create(['userLevel' => 0]);
    $this->customer->assignRole('customer');
    $this->admin = User::factory()->create(['userLevel' => 5]);
    $this->admin->assignRole('admin');
    $this->staff = User::factory()->create(['userLevel' => 1, 'branch_id' => $this->branch->id]);
    $this->staff->assignRole('staff');

    $this->booking = Booking::factory()->create(['user_id' => $this->customer->id, 'total_price' => 300]);
    $this->booking->cars()->attach($this->car->id, [
        'rental_start' => $this->booking->start_date, 'rental_end' => $this->booking->end_date, 'quantity' => 1, 'price' => 300,
    ]);
    $this->payment = app(PaymentService::class)->startCheckout($this->booking->fresh(), 'sandbox');
});

it('renders the public storefront', function () {
    $this->get('/')->assertOk()->assertSee('X-Trail')->assertSee('Top 10 this month');
    $this->get(route('cars.index'))->assertOk()->assertSee('Browse cars');
    $this->get(route('cars.index', ['type' => 'SUV', 'q' => 'nis']))->assertOk()->assertSee('X-Trail');
    $this->get(route('cars.show', $this->car))->assertOk()->assertSee('data-car-viewer', false)->assertSee('Book this car');
    $this->get(route('login'))->assertOk();
    $this->get(route('register', ['email' => 'a@b.co']))->assertOk()->assertSee('a@b.co');
    $this->get(route('password.request'))->assertOk();
});

it('renders the customer area', function () {
    $this->actingAs($this->customer);

    $this->get(route('home'))->assertOk()->assertSee('Complete payment');
    $this->get(route('bookings.index'))->assertOk()->assertSee($this->booking->reference);
    $this->get(route('bookings.create', ['car' => $this->car->id]))->assertOk()->assertSee('Plan your trip');
    $this->get(route('bookings.show', $this->booking))->assertOk()->assertSee('Choose how to pay')->assertSee('Sandbox');
    $this->get(route('bookings.edit', $this->booking))->assertOk();
    $this->get(route('payments.sandbox.show', $this->payment))->assertOk()->assertSee('Approve payment');
    $this->get(route('payments.return', $this->payment))->assertOk()->assertSee('Confirming your payment');
    $this->get(route('profile.edit'))->assertOk();
    $this->get(route('notifications.index'))->assertOk();
});

it('renders the admin console', function () {
    $this->actingAs($this->admin);

    $this->get(route('home'))->assertRedirect(route('admin.dashboard'));
    foreach ([
        route('admin.dashboard'),
        route('admin.bookings.index'),
        route('admin.bookings.index', ['status' => 'pending', 'payment_status' => 'unpaid', 'search' => 'EC-']),
        route('admin.bookings.show', $this->booking),
        route('admin.cars.index'),
        route('admin.cars.create'),
        route('admin.cars.show', $this->car),
        route('admin.cars.edit', $this->car),
        route('admin.users.index'),
        route('admin.users.create'),
        route('admin.users.edit', $this->staff),
        route('admin.payments.index'),
        route('admin.payments.show', $this->payment),
        route('admin.branches.index'),
        route('admin.branches.create'),
        route('admin.branches.edit', $this->branch),
        route('profile.edit'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('renders the staff console scoped to the branch', function () {
    $this->actingAs($this->staff);

    $this->get(route('home'))->assertRedirect(route('staff.dashboard'));
    $this->get(route('staff.dashboard'))->assertOk();
    $this->get(route('staff.bookings.index'))->assertOk()->assertSee($this->booking->reference);
    $this->get(route('staff.bookings.show', $this->booking))->assertOk();
    $this->get(route('staff.cars.index'))->assertOk()->assertSee('X-Trail');
    $this->get(route('admin.dashboard'))->assertForbidden();
});

it('creates, updates and deletes a car with the new showroom fields', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.cars.store'), [
        'brand' => 'Perodua', 'model' => 'Ativa', 'year' => 2024, 'type' => 'SUV', 'transmission' => 'Automatic',
        'seats' => 5, 'fuel_type' => 'Petrol', 'color' => '#123abc', 'branch_id' => $this->branch->id,
        'price_per_day' => 99, 'plate_number' => 'vat 1', 'status' => 'available', 'is_featured' => '1',
    ])->assertRedirect();

    $car = Car::where('model', 'Ativa')->firstOrFail();
    expect($car->plate_number)->toBe('VAT 1')->and($car->paint_color)->toBe('#123abc')->and($car->is_featured)->toBeTrue();

    $this->put(route('admin.cars.update', $car), array_merge($car->only(['brand', 'model', 'type', 'transmission', 'seats', 'fuel_type', 'branch_id', 'plate_number', 'status']), ['price_per_day' => 120, 'is_featured' => '0']))->assertRedirect();
    expect((float) $car->fresh()->price_per_day)->toBe(120.0)->and($car->fresh()->is_featured)->toBeFalse();

    $this->delete(route('admin.cars.destroy', $car))->assertRedirect(route('admin.cars.index'));
    expect(Car::find($car->id))->toBeNull();
});

it('adds security headers and serves a branded 404', function () {
    $response = $this->get('/');
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    $this->get('/definitely-not-a-page')->assertNotFound()->assertSee('Lost your way?');
});

it('keeps guests out of customer pages', function () {
    $this->get(route('bookings.index'))->assertRedirect(route('login'));
    $this->get(route('bookings.create'))->assertRedirect(route('login'));
});
