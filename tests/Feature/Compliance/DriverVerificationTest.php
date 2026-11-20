<?php

require_once __DIR__.'/../Rentals/RentalHelpers.php';

use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\DriverProfile;
use App\Models\User;
use App\Notifications\DriverVerificationReviewed;
use App\Services\Rentals\RentalOperationsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Storage::fake('public');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

function verificationPayload(array $overrides = []): array
{
    return array_merge([
        'licence_number' => 'd 1234567',
        'licence_expiry' => now()->addYears(2)->toDateString(),
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'id_type' => 'ic',
        'id_number' => '900101 14 5555',
        'licence_front' => UploadedFile::fake()->image('front.jpg'),
        'id_document' => UploadedFile::fake()->image('ic.jpg'),
        'consent' => '1',
    ], $overrides);
}

function verifiedProfile(User $user, array $overrides = []): DriverProfile
{
    return DriverProfile::create(array_merge([
        'user_id' => $user->id, 'licence_number' => 'D1', 'licence_expiry' => now()->addYears(2), 'date_of_birth' => now()->subYears(30),
        'id_type' => 'ic', 'id_number' => '900101145555', 'licence_front_path' => 'x/f.jpg', 'id_document_path' => 'x/i.jpg',
        'status' => VerificationStatus::Verified, 'submitted_at' => now(),
    ], $overrides));
}

it('stores submitted documents privately and encrypts the ID numbers at rest', function () {
    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload())
        ->assertRedirect(route('verification.show'))->assertSessionHas('success');

    $profile = $this->customer->fresh()->driverProfile;
    expect($profile->status)->toBe(VerificationStatus::Pending)
        ->and($profile->licence_number)->toBe('D 1234567')
        ->and($profile->id_number)->toBe('900101145555');

    $raw = DB::table('driver_profiles')->first();
    expect($raw->licence_number)->not->toContain('1234567')->and($raw->id_number)->not->toContain('900101');

    Storage::disk('local')->assertExists($profile->licence_front_path);
    Storage::disk('local')->assertExists($profile->id_document_path);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('rejects under-age drivers, expired licences and missing consent', function () {
    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload([
        'date_of_birth' => now()->subYears(18)->toDateString(),
        'licence_expiry' => now()->subDay()->toDateString(),
        'consent' => null,
    ]))->assertSessionHasErrors(['date_of_birth', 'licence_expiry', 'consent']);

    expect(DriverProfile::count())->toBe(0);
});

it('resets to review and deletes replaced documents on resubmission', function () {
    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload());
    $profile = $this->customer->fresh()->driverProfile;
    $profile->update(['status' => VerificationStatus::Rejected, 'rejection_reason' => 'Blurry']);
    $oldFront = $profile->licence_front_path;

    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload(['id_document' => null]))->assertSessionHasNoErrors();

    $profile->refresh();
    expect($profile->status)->toBe(VerificationStatus::Pending)->and($profile->rejection_reason)->toBeNull();
    Storage::disk('local')->assertMissing($oldFront);
    Storage::disk('local')->assertExists($profile->id_document_path); // kept
});

it('lets staff verify or send back a driver, notifying the customer', function () {
    Notification::fake();
    $staff = branchStaff(Branch::factory()->create());
    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload());
    $profile = $this->customer->fresh()->driverProfile;

    $this->actingAs($staff)->get(route('ops.verifications.index'))->assertOk()->assertSee($this->customer->name);
    $this->actingAs($staff)->get(route('ops.verifications.show', $profile))->assertOk()->assertSee('D 1234567');

    $this->actingAs($staff)->put(route('ops.verifications.update', $profile), ['decision' => 'reject'])->assertSessionHasErrors('reason');
    $this->actingAs($staff)->put(route('ops.verifications.update', $profile), ['decision' => 'reject', 'reason' => 'Photo is blurry'])->assertRedirect();
    expect($profile->fresh()->status)->toBe(VerificationStatus::Rejected);

    $this->actingAs($staff)->put(route('ops.verifications.update', $profile), ['decision' => 'approve'])->assertRedirect();
    expect($profile->fresh()->isVerified())->toBeTrue()->and($profile->fresh()->reviewed_by)->toBe($staff->id);

    Notification::assertSentToTimes($this->customer, DriverVerificationReviewed::class, 2);
});

it('shows documents only to their owner and staff, and keeps customers out of the queue', function () {
    $this->actingAs($this->customer)->post(route('verification.store'), verificationPayload());
    $profile = $this->customer->fresh()->driverProfile;
    $url = route('verification.document', [$profile, 'licence_front']);

    $this->actingAs($this->customer)->get($url)->assertOk();
    $this->actingAs(branchStaff(Branch::factory()->create()))->get($url)->assertOk();
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    $this->actingAs($this->customer)->get(route('ops.verifications.index'))->assertForbidden();
    $this->actingAs($this->customer)->get(route('verification.document', [$profile, 'secrets']))->assertNotFound();
});

describe('with verification required', function () {
    beforeEach(function () {
        config(['compliance.require_verified_driver' => true]);
        $this->car = Car::factory()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);
        $this->booking->cars()->attach($this->car->id, ['rental_start' => $this->booking->start_date, 'rental_end' => $this->booking->end_date, 'quantity' => 1, 'price' => 100]);
    });

    it('blocks approval until the driver is verified', function () {
        $admin = adminUser();

        expect($this->booking->approvalBlocker())->toContain('hasn\'t submitted');
        $this->actingAs($admin)->patch(route('bookings.approve', $this->booking))->assertSessionHas('error');
        expect($this->booking->fresh()->status)->toBe('pending');

        verifiedProfile($this->customer);
        $this->actingAs($admin)->patch(route('bookings.approve', $this->booking->fresh()))->assertSessionHas('success');
        expect($this->booking->fresh()->status)->toBe('approved');
    });

    it('blocks approval when the licence expires before the return date or the driver is too young', function () {
        verifiedProfile($this->customer, ['licence_expiry' => now()->addDays(6)]);
        expect($this->booking->fresh()->approvalBlocker())->toContain('expires');

        $this->customer->driverProfile()->update(['licence_expiry' => now()->addYear(), 'date_of_birth' => now()->subYears(19)]);
        expect($this->booking->fresh()->approvalBlocker())->toContain('at least 21');
    });

    it('blocks pick-up for an unverified driver', function () {
        $this->booking->update(['status' => 'approved']);
        $this->travelTo($this->booking->start_date->copy()->setTime(11, 0));

        expect(app(RentalOperationsService::class)->pickupBlocker($this->booking->fresh(), $this->car))->toContain('submitted');
    });

    it('prompts the customer to verify on their booking page', function () {
        $this->actingAs($this->customer)->get(route('bookings.show', $this->booking))->assertOk()->assertSee('Verify your driving licence to get this booking confirmed');
    });
});
