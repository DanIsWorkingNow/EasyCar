<?php

require_once __DIR__.'/RentalHelpers.php';

use App\Models\Car;
use App\Services\Rentals\ChargeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->car = Car::factory()->create(['price_per_day' => 120]);
    $this->booking = approvedBooking([$this->car]);   // due 12 Dec 18:00, 60 min grace
    $this->calc = app(ChargeCalculator::class);
});

function returnedAt(string $time): Carbon
{
    return Carbon::parse($time, config('app.timezone'));
}

it('allows the grace period, then charges per started 24 hours', function () {
    expect($this->calc->lateDays($this->booking, returnedAt('2026-12-12 18:59')))->toBe(0)
        ->and($this->calc->lateDays($this->booking, returnedAt('2026-12-12 19:01')))->toBe(1)
        ->and($this->calc->lateDays($this->booking, returnedAt('2026-12-13 17:59')))->toBe(1)
        ->and($this->calc->lateDays($this->booking, returnedAt('2026-12-13 18:01')))->toBe(2);
});

it('applies the late fee multiplier', function () {
    config(['rentals.late_fee_multiplier' => 1.5]);

    $lines = $this->calc->forReturn($this->booking, $this->car, 100, 8, returnedAt('2026-12-12 20:00'), 200, 8);

    expect($lines)->toHaveCount(1)->and($lines[0]['amount'])->toEqual(180.0); // 1 × 120 × 1.5
});

it('charges nothing for an on-time return with more fuel than at pick-up', function () {
    expect($this->calc->forReturn($this->booking, $this->car, 100, 4, returnedAt('2026-12-12 10:00'), 900, 8))->toBe([]);
});

it('charges excess mileage only when an allowance is configured', function () {
    $args = [$this->booking, $this->car, 1000, 8, returnedAt('2026-12-12 10:00'), 2200, 8];
    expect($this->calc->forReturn(...$args))->toBe([]);

    config(['rentals.daily_km_allowance' => 300, 'rentals.excess_km_rate' => 0.5]);
    $lines = $this->calc->forReturn(...$args);   // 1200 km driven, 900 allowed

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['quantity'])->toEqual(300.0)
        ->and($lines[0]['amount'])->toEqual(150.0);
});
