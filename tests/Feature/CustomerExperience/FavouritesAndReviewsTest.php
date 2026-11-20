<?php

require_once __DIR__.'/../Rentals/RentalHelpers.php';

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\PromoCode;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100]);
    $this->customer = User::factory()->create(['name' => 'Aisha Rahman']);
});

function completedBooking(User $user, Car $car, ?string $completedAt = null): Booking
{
    $booking = approvedBooking([$car], ['user_id' => $user->id]);
    $booking->update(['status' => 'completed', 'completed_at' => $completedAt ?? now()->subDay()]);

    return $booking->fresh();
}

describe('My List', function () {
    it('toggles a car in and out of the list as JSON', function () {
        $this->actingAs($this->customer)->postJson(route('favourites.toggle', $this->car))
            ->assertOk()->assertJson(['favourited' => true, 'count' => 1]);

        $this->get(route('favourites.index'))->assertOk()->assertSee($this->car->name);

        $this->postJson(route('favourites.toggle', $this->car))->assertJson(['favourited' => false, 'count' => 0]);
        expect($this->customer->favourites()->count())->toBe(0);
    });

    it('works without JavaScript and needs a sign-in', function () {
        $this->post(route('favourites.toggle', $this->car))->assertRedirect(route('login'));

        $this->actingAs($this->customer)->from(route('cars.show', $this->car))
            ->post(route('favourites.toggle', $this->car))
            ->assertRedirect(route('cars.show', $this->car))
            ->assertSessionHas('success');
    });

    it('shows a My List row on the storefront and home', function () {
        $this->customer->favourites()->attach($this->car);

        $this->actingAs($this->customer)->get('/')->assertOk()->assertSee('My List')->assertSee('aria-pressed="true"', false);
        $this->get(route('home'))->assertOk()->assertSee('My List');
    });
});

describe('Reviews', function () {
    it('lets a customer review a car from a completed trip, once', function () {
        $booking = completedBooking($this->customer, $this->car);

        $this->actingAs($this->customer)->get(route('bookings.show', $booking))->assertSee('Rate your trip');

        $this->post(route('reviews.store', $booking), ['car_id' => $this->car->id, 'rating' => 4, 'title' => 'Smooth', 'body' => 'Clean and easy pick-up.'])
            ->assertRedirect()->assertSessionHas('success');

        // Re-submitting edits the same review rather than adding another.
        $this->post(route('reviews.store', $booking), ['car_id' => $this->car->id, 'rating' => 5]);

        expect(Review::count())->toBe(1)->and(Review::first()->rating)->toBe(5);
    });

    it('refuses reviews for unfinished, old or other people\'s trips', function () {
        $upcoming = approvedBooking([$this->car], ['user_id' => $this->customer->id]);
        $this->actingAs($this->customer)->post(route('reviews.store', $upcoming), ['car_id' => $this->car->id, 'rating' => 5])
            ->assertSessionHasErrors('rating');

        $old = completedBooking($this->customer, Car::factory()->create(), now()->subDays(91)->toDateTimeString());
        $this->post(route('reviews.store', $old), ['car_id' => $old->cars->first()->id, 'rating' => 5])
            ->assertSessionHasErrors('rating');

        $theirs = completedBooking(User::factory()->create(), Car::factory()->create());
        $this->post(route('reviews.store', $theirs), ['car_id' => $theirs->cars->first()->id, 'rating' => 1])->assertForbidden();

        $mine = completedBooking($this->customer, Car::factory()->create());
        $this->post(route('reviews.store', $mine), ['car_id' => $this->car->id, 'rating' => 1])
            ->assertSessionHasErrors('car_id'); // car wasn't on that booking

        expect(Review::count())->toBe(0);
    });

    it('shows the rating summary, reviews and structured data on the car page', function () {
        foreach ([5, 4] as $rating) {
            $booking = completedBooking(User::factory()->create(['name' => 'Ben Tan']), $this->car);
            Review::create(['booking_id' => $booking->id, 'car_id' => $this->car->id, 'user_id' => $booking->user_id, 'rating' => $rating, 'body' => "Body {$rating}"]);
        }
        $hidden = completedBooking(User::factory()->create(), $this->car);
        Review::create(['booking_id' => $hidden->id, 'car_id' => $this->car->id, 'user_id' => $hidden->user_id, 'rating' => 1, 'body' => 'Rude words', 'is_published' => false]);

        $this->get(route('cars.show', $this->car))
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('2 verified reviews')
            ->assertSee('Ben T.')            // first name + initial only
            ->assertDontSee('Rude words')
            ->assertSee('"aggregateRating"', false)
            ->assertSee('"reviewCount":2', false);

        // Tiles carry the average too.
        $this->get(route('cars.index'))->assertSee('4.5');
    });

    it('lets branch staff reply and hide reviews for their own branch only', function () {
        $booking = completedBooking($this->customer, $this->car);
        $review = Review::create(['booking_id' => $booking->id, 'car_id' => $this->car->id, 'user_id' => $this->customer->id, 'rating' => 2, 'body' => 'Late handover']);
        $staff = branchStaff($this->branch);

        $this->actingAs($staff)->get(route('ops.reviews.index'))->assertOk()->assertSee('Late handover');
        $this->patch(route('ops.reviews.update', $review), ['reply' => 'Sorry — we have added a second counter.'])->assertSessionHas('success');
        $this->patch(route('ops.reviews.update', $review), ['is_published' => 0]);

        $review->refresh();
        expect($review->reply)->toContain('second counter')->and($review->replied_by)->toBe($staff->id)->and($review->is_published)->toBeFalse();

        $otherStaff = branchStaff(Branch::factory()->create());
        $this->actingAs($otherStaff)->get(route('ops.reviews.index'))->assertDontSee('Late handover');
        $this->patch(route('ops.reviews.update', $review), ['is_published' => 1])->assertForbidden();

        $this->actingAs($this->customer)->get(route('ops.reviews.index'))->assertForbidden();
    });
});

describe('Admin promotions and extras', function () {
    it('manages promo codes and only deactivates used ones', function () {
        $admin = adminUser();

        $this->actingAs($admin)->post(route('admin.promo-codes.store'), [
            'code' => 'raya25', 'type' => 'percent', 'value' => 25, 'max_discount' => 80, 'per_user_limit' => 1, 'is_active' => 1,
        ])->assertRedirect(route('admin.promo-codes.index'));

        $promo = PromoCode::where('code', 'RAYA25')->firstOrFail();
        $this->get(route('admin.promo-codes.index'))->assertOk()->assertSee('RAYA25')->assertSee('25% off (up to RM 80)');

        $this->post(route('admin.promo-codes.store'), ['code' => 'RAYA25', 'type' => 'percent', 'value' => 120, 'per_user_limit' => 1])
            ->assertSessionHasErrors(['code', 'value']);

        approvedBooking([$this->car], ['promo_code_id' => $promo->id]);
        $this->delete(route('admin.promo-codes.destroy', $promo));
        expect($promo->fresh())->not->toBeNull()->and($promo->fresh()->is_active)->toBeFalse();
    });

    it('manages extras and keeps customers out', function () {
        $this->actingAs(adminUser())->post(route('admin.addons.store'), [
            'name' => 'Phone holder', 'icon' => 'phone', 'pricing' => 'per_booking', 'price' => 5, 'max_quantity' => 1, 'is_active' => 1,
        ])->assertRedirect(route('admin.addons.index'));

        $this->get(route('admin.addons.index'))->assertSee('Phone holder')->assertSee('RM 5.00 per trip');

        $this->actingAs($this->customer)->get(route('admin.addons.index'))->assertForbidden();
        $this->get(route('admin.promo-codes.index'))->assertForbidden();
    });
});
