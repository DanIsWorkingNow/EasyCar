<?php

require_once __DIR__.'/../Rentals/RentalHelpers.php';

use App\Livewire\Booking\CarAvailabilityPicker;
use App\Models\Booking;
use App\Models\BookingDeposit;
use App\Models\Branch;
use App\Models\Car;
use App\Models\LoyaltyTransaction;
use App\Models\PricingRule;
use App\Models\User;
use App\Services\BookingCreationService;
use App\Services\PaymentService;
use App\Services\Pricing\LoyaltyService;
use App\Services\Pricing\PricingService;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100, 'type' => 'Sedan']);
    $this->user = User::factory()->create();
});

function bookFor(User $user, Car $car, string $start, string $end, bool $points = false): Booking
{
    return app(BookingCreationService::class)->create($user, [$car->id], $start, $end, '127.0.0.1', [], null, $points);
}

describe('Dynamic pricing', function () {
    it('applies weekend and season rules per day, highest priority winning', function () {
        PricingRule::create(['name' => 'Weekend', 'kind' => 'weekend', 'days_of_week' => [6, 7], 'adjustment_percent' => 10]);
        PricingRule::create(['name' => 'Holidays', 'kind' => 'season', 'starts_on' => '2026-12-24', 'ends_on' => '2026-12-31', 'adjustment_percent' => 20, 'priority' => 1]);
        PricingRule::create(['name' => 'Low priority', 'kind' => 'season', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'adjustment_percent' => 50]);

        // Wed 23 Dec (low-priority season +50), Thu 24 (holiday +20), Sat 26 (+20 and weekend +10)
        $rates = app(PricingService::class)->dailyRates($this->car, '2026-12-23', '2026-12-26');

        expect($rates['2026-12-23']['rate'])->toBe(150.0)
            ->and($rates['2026-12-24']['rate'])->toBe(120.0)
            ->and($rates['2026-12-26']['rate'])->toBe(132.0)
            ->and($rates['2026-12-26']['adjustments'])->toBe(['Holidays +20%', 'Weekend +10%']);
    });

    it('limits rules to a car type or branch and ignores inactive ones', function () {
        PricingRule::create(['name' => 'SUV surge', 'kind' => 'weekend', 'days_of_week' => [1, 2, 3, 4, 5, 6, 7], 'adjustment_percent' => 30, 'car_type' => 'SUV']);
        PricingRule::create(['name' => 'Other branch', 'kind' => 'weekend', 'days_of_week' => [1, 2, 3, 4, 5, 6, 7], 'adjustment_percent' => 30, 'branch_id' => Branch::factory()->create()->id]);
        PricingRule::create(['name' => 'Off', 'kind' => 'weekend', 'days_of_week' => [1, 2, 3, 4, 5, 6, 7], 'adjustment_percent' => 30, 'is_active' => false]);

        expect(app(PricingService::class)->carPrice($this->car, '2026-12-01', '2026-12-03')['total'])->toBe(300.0);
    });

    it('gives weekly and monthly rates on long rentals', function () {
        $pricing = app(PricingService::class);

        expect($pricing->carPrice($this->car, '2027-02-01', '2027-02-06')['total'])->toBe(600.0)      // 6 days, no discount
            ->and($pricing->carPrice($this->car, '2027-02-01', '2027-02-07')['total'])->toBe(630.0)    // 7 days −10%
            ->and($pricing->carPrice($this->car, '2027-02-01', '2027-02-28')['total'])->toBe(2240.0)   // 28 days −20%
            ->and($pricing->carPrice($this->car, '2027-02-01', '2027-02-07')['notes'])->toContain('Weekly rate −10%');
    });

    it('prices bookings and quotes with the rules and snapshots the deposit', function () {
        PricingRule::create(['name' => 'Weekend', 'kind' => 'weekend', 'days_of_week' => [6, 7], 'adjustment_percent' => 10]);
        $start = now()->next('Friday')->addWeek()->toDateString();
        $end = Carbon::parse($start)->addDays(2)->toDateString(); // Fri, Sat, Sun

        $booking = bookFor($this->user, $this->car, $start, $end);

        expect($booking->total_price)->toBe(320.0)
            ->and((float) $booking->cars->first()->pivot->price)->toEqual(320.0)
            ->and($booking->deposit_amount)->toBe(200.0); // config default for a Sedan

        $quote = app(BookingCreationService::class)->quote([$this->car->id], $start, $end);
        expect($quote['total'])->toBe(320.0)->and($quote['deposit'])->toBe(200.0)
            ->and($quote['lines'][0]['notes'])->toBe(['Weekend +10% (2 days)']);
    });

    it('lets admins manage rules and preview rates', function () {
        $this->actingAs(adminUser())->post(route('admin.pricing.store'), [
            'name' => 'Raya', 'kind' => 'season', 'starts_on' => '2027-03-18', 'ends_on' => '2027-03-25', 'adjustment_percent' => 25, 'is_active' => 1,
        ])->assertRedirect(route('admin.pricing.index'));

        $this->post(route('admin.pricing.store'), ['name' => 'Bad', 'kind' => 'weekend', 'adjustment_percent' => 0])
            ->assertSessionHasErrors(['days_of_week', 'adjustment_percent']);

        $this->get(route('admin.pricing.index', ['car' => $this->car->id]))->assertOk()->assertSee('Raya')->assertSee('+25%')->assertSee('Daily rate preview');

        $this->actingAs($this->user)->get(route('admin.pricing.index'))->assertForbidden();
    });
});

describe('Security deposits', function () {
    beforeEach(function () {
        $this->staff = branchStaff($this->branch);
        $this->booking = approvedBooking([$this->car], ['user_id' => $this->user->id, 'deposit_amount' => 200]);
        at('2026-12-10 10:00');
    });

    function handover(array $extra = []): HandoverData
    {
        return new HandoverData(...array_merge(['odometer' => 1000, 'fuel' => 8, 'signature' => signaturePng(), 'signedByName' => 'Customer'], $extra));
    }

    it('requires the deposit at pick-up and records it once', function () {
        $ops = app(RentalOperationsService::class);

        expect(fn () => $ops->pickUp($this->booking, $this->car, $this->staff, handover()))
            ->toThrow(ValidationException::class, 'security deposit');

        $ops->pickUp($this->booking, $this->car, $this->staff, handover(['deposit' => ['method' => 'card_hold', 'reference' => 'AUTH123']]));

        $deposit = $this->booking->fresh()->deposit;
        expect($deposit->amount)->toBe(200.0)->and($deposit->status)->toBe(BookingDeposit::HELD)->and($deposit->reference)->toBe('AUTH123');
    });

    it('applies the deposit to extra charges and releases the rest', function () {
        $ops = app(RentalOperationsService::class);
        $ops->pickUp($this->booking, $this->car, $this->staff, handover(['deposit' => ['method' => 'cash', 'reference' => null]]));
        at('2026-12-12 17:00');
        $ops->returnCar($this->booking->fresh(), $this->car, $this->staff, handover(['odometer' => 1200, 'fuel' => 6])); // 2 eighths short = RM 30

        $this->actingAs($this->staff)->get(route('staff.bookings.show', $this->booking))->assertSee('Use for charges (RM 30.00)');

        $this->post(route('ops.deposit.settle', $this->booking), ['action' => 'apply'])->assertSessionHas('success');

        $booking = $this->booking->fresh(['deposit', 'charges']);
        expect($booking->deposit->status)->toBe(BookingDeposit::APPLIED)
            ->and($booking->deposit->applied_amount)->toBe(30.0)
            ->and($booking->deposit->releasedAmount())->toBe(170.0)
            ->and($booking->outstandingCharges())->toEqual(0.0)
            ->and($booking->payments()->where('purpose', 'charges')->first()->meta['method'])->toBe('deposit');
    });

    it('only releases when charges exceed the deposit, and stops other branches', function () {
        $ops = app(RentalOperationsService::class);
        $ops->pickUp($this->booking, $this->car, $this->staff, handover(['deposit' => ['method' => 'cash', 'reference' => null]]));
        at('2026-12-12 17:00');
        $ops->returnCar($this->booking->fresh(), $this->car, $this->staff, handover(['odometer' => 1200]), [['type' => 'damage', 'description' => 'Bumper', 'amount' => 500]]);

        $this->actingAs(branchStaff(Branch::factory()->create()))->post(route('ops.deposit.settle', $this->booking), ['action' => 'release'])->assertForbidden();

        $this->actingAs($this->staff)->post(route('ops.deposit.settle', $this->booking), ['action' => 'apply'])->assertSessionHasErrors('deposit');
        $this->post(route('ops.deposit.settle', $this->booking), ['action' => 'release']);

        expect($this->booking->fresh()->deposit->status)->toBe(BookingDeposit::RELEASED)
            ->and($this->booking->fresh()->outstandingCharges())->toEqual(500.0);
    });
});

describe('Loyalty points', function () {
    it('earns points once when a booking completes', function () {
        $booking = approvedBooking([$this->car], ['user_id' => $this->user->id, 'total_price' => 345.60]);

        $booking->update(['status' => 'completed', 'completed_at' => now()]);
        $booking->update(['status' => 'completed']); // no double award
        $booking->touch();

        expect($this->user->pointsBalance())->toBe(345)
            ->and(LoyaltyTransaction::where('type', 'earn')->count())->toBe(1);
    });

    it('redeems points within the cap and returns them on cancellation', function () {
        app(LoyaltyService::class)->adjust($this->user, 5000, 'Welcome bonus', adminUser());
        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString(); // RM 300

        $booking = bookFor($this->user, $this->car, $start, $end, points: true);

        // Max 50% of RM 300 = RM 150 = 3000 points at RM 0.05
        expect($booking->points_redeemed)->toBe(3000)
            ->and($booking->points_discount)->toBe(150.0)
            ->and($booking->total_price)->toBe(150.0)
            ->and($this->user->pointsBalance())->toBe(2000);

        $booking->cancel($this->user);
        expect($this->user->pointsBalance())->toBe(5000);

        // Cancelling again (or rejecting) can't return them twice.
        $booking->update(['status' => 'rejected']);
        expect($this->user->pointsBalance())->toBe(5000);
    });

    it('refuses to redeem below the minimum and returns excess points when re-priced lower', function () {
        app(LoyaltyService::class)->adjust($this->user, 50, 'Small', adminUser());
        expect(fn () => bookFor($this->user, $this->car, now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString(), true))
            ->toThrow(ValidationException::class);

        app(LoyaltyService::class)->adjust($this->user, 4950, 'Top up', adminUser());
        $booking = bookFor($this->user, $this->car, now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString(), true); // 3000 pts
        app(BookingCreationService::class)->update($booking, [$this->car->id], now()->addDays(10)->toDateString(), now()->addDays(10)->toDateString()); // 1 day

        $booking->refresh();
        expect($booking->points_redeemed)->toBe(1000)     // 50% of RM 100
            ->and($booking->total_price)->toBe(50.0)
            ->and($this->user->pointsBalance())->toBe(4000);
    });

    it('shows the points discount on the invoice', function () {
        app(LoyaltyService::class)->adjust($this->user, 1000, 'Bonus', adminUser());
        $booking = bookFor($this->user, $this->car, now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString(), true);
        $booking->update(['status' => 'approved']);

        $invoice = app(PaymentService::class)->recordCounterPayment($booking, 'booking', 'cash', null, branchStaff($this->branch))->invoices()->firstOrFail();
        $lines = collect($invoice->lines);

        expect($invoice->total)->toEqual(250.0)
            ->and($lines->sum('amount'))->toEqual(250.0)
            ->and($lines->last()['description'])->toContain('1,000 points');
    });

    it('lets customers use points in the booking flow and see their history', function () {
        app(LoyaltyService::class)->adjust($this->user, 2000, 'Bonus', adminUser());
        $this->actingAs($this->user);

        Livewire::test(CarAvailabilityPicker::class, ['preselectedCarId' => $this->car->id])
            ->set('startDate', now()->addDays(10)->toDateString())
            ->set('endDate', now()->addDays(12)->toDateString())
            ->assertViewHas('quote', fn ($q) => $q['points_available'] === 2000 && $q['points'] === 0)
            ->set('usePoints', true)
            ->assertViewHas('quote', fn ($q) => $q['total'] === 200.0)
            ->set('acceptTerms', true)
            ->call('createBooking')
            ->assertRedirect();

        $this->get(route('rewards.index'))->assertOk()->assertSee('Redeemed on booking')->assertSee('Bonus');
        expect($this->user->pointsBalance())->toBe(0);
    });

    it('lets admins adjust points but not below zero, and exposes points via the API', function () {
        $admin = adminUser();
        $this->actingAs($admin)->post(route('admin.users.points', $this->user), ['points' => 300, 'reason' => 'Goodwill'])->assertRedirect();
        $this->post(route('admin.users.points', $this->user), ['points' => -500, 'reason' => 'Oops'])->assertSessionHasErrors('points');
        expect($this->user->pointsBalance())->toBe(300);

        $this->user->loyaltyTransactions()->create(['points' => 700, 'type' => 'adjustment', 'description' => 'More']);
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/bookings', [
            'start_date' => now()->addDays(10)->toDateString(), 'end_date' => now()->addDays(12)->toDateString(),
            'cars' => [$this->car->id], 'use_points' => true,
        ])->assertCreated()->assertJsonPath('data.points_redeemed', 1000)->assertJsonPath('data.points_discount', 50)->assertJsonPath('data.deposit_amount', 200);
    });
});
