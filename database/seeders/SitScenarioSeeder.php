<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Car;
use App\Models\DriverProfile;
use App\Models\Review;
use App\Models\User;
use App\Services\BookingCreationService;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * SIT (System Integration Testing) scenario data, on top of the demo seed.
 * Dates are relative to today so the pack can be re-run any day.
 *
 *   sit.verified@easycar.test  verified driver with: a booking to pick up
 *                              today, a car on trip due back today, a
 *                              completed trip to review, and a pending
 *                              paid booking awaiting approval
 *   sit.new@easycar.test       unverified driver with a pending booking
 *                              (approval must be blocked)
 *
 * Password for both: password
 */
class SitScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $verified = User::updateOrCreate(['email' => 'sit.verified@easycar.test'], ['name' => 'Nur Aisyah Verified', 'password' => bcrypt('password'), 'userLevel' => 0]);
        $verified->syncRoles(['customer']);
        $new = User::updateOrCreate(['email' => 'sit.new@easycar.test'], ['name' => 'Hakim Unverified', 'password' => bcrypt('password'), 'userLevel' => 0]);
        $new->syncRoles(['customer']);
        $staff = User::where('email', 'staff.bangi@easycar.com')->firstOrFail();

        DriverProfile::updateOrCreate(['user_id' => $verified->id], [
            'licence_number' => 'D 9001122', 'licence_expiry' => now()->addYears(3)->toDateString(), 'date_of_birth' => '1993-04-02',
            'id_type' => 'ic', 'id_number' => '930402-10-5544', 'phone' => '012-3456789',
            'licence_front_path' => 'sit/licence.jpg', 'id_document_path' => 'sit/ic.jpg',
            'status' => 'verified', 'submitted_at' => now()->subDays(20), 'reviewed_by' => $staff->id, 'reviewed_at' => now()->subDays(19),
        ]);

        $creation = app(BookingCreationService::class);
        $ops = app(RentalOperationsService::class);
        $myvi = Car::where('model', 'Myvi')->firstOrFail();      // Bangi
        $almera = Car::where('model', 'Almera')->firstOrFail();  // Bangi
        $exora = Car::where('model', 'Exora')->firstOrFail();    // Bangi
        $bezza = Car::where('model', 'Bezza')->firstOrFail();    // Bangi
        $city = Car::where('model', 'City')->firstOrFail();      // Gombak

        // 1) Completed trip last week → reviewable, points earned.
        Carbon::setTestNow(now()->subDays(12)->setTime(9, 0));
        $done = $creation->create($verified, [$city->id], now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString(), '127.0.0.1');
        $done->update(['status' => 'approved', 'payment_status' => 'paid', 'approved_at' => now()]);
        Carbon::setTestNow(now()->addDays(3)->setTime(10, 0));
        $ops->pickUp($done->fresh(), $city, $staff, $this->handover(15200, 8, ['method' => 'card_hold', 'reference' => 'SIT-PREAUTH-1']));
        Carbon::setTestNow(now()->addDays(2)->setTime(17, 30));
        $ops->returnCar($done->fresh(), $city, $staff, $this->handover(15480, 8));
        Carbon::setTestNow();
        $done->fresh()->deposit?->update(['status' => 'released', 'settled_by' => $staff->id, 'settled_at' => now()->subDays(4)]);

        // 2) On trip, due back today (picked up 2 days ago).
        Carbon::setTestNow(now()->subDays(5)->setTime(9, 0));
        $onTrip = $creation->create($verified, [$almera->id], now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString(), '127.0.0.1');
        $onTrip->update(['status' => 'approved', 'payment_status' => 'paid', 'approved_at' => now()]);
        Carbon::setTestNow(now()->addDays(3)->setTime(10, 15));
        $ops->pickUp($onTrip->fresh(), $almera, $staff, $this->handover(20100, 8, ['method' => 'cash', 'reference' => null]));
        Carbon::setTestNow();

        // 3) Approved + paid, starts today → ready for pick-up.
        Carbon::setTestNow(now()->subDays(4));
        $pickup = $creation->create($verified, [$myvi->id], now()->addDays(4)->toDateString(), now()->addDays(6)->toDateString(), '127.0.0.1');
        Carbon::setTestNow();
        $pickup->update(['status' => 'approved', 'payment_status' => 'paid', 'approved_at' => now()->subDays(3)]);

        // 4) Pending + paid, verified → approvable.
        $pendingOk = $creation->create($verified, [$exora->id], now()->addDays(14)->toDateString(), now()->addDays(16)->toDateString(), '127.0.0.1');
        $pendingOk->update(['payment_status' => 'paid']);

        // 5) Pending, unverified driver → approval blocked.
        $creation->create($new, [$bezza->id], now()->addDays(20)->toDateString(), now()->addDays(22)->toDateString(), '127.0.0.1');

        $this->command?->info("SIT: completed={$done->reference} on_trip={$onTrip->reference} pickup={$pickup->reference} pending={$pendingOk->reference}");
    }

    private function handover(int $odometer, int $fuel, ?array $deposit = null): HandoverData
    {
        $img = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($img);
        $png = 'data:image/png;base64,'.base64_encode((string) ob_get_clean());

        return new HandoverData(odometer: $odometer, fuel: $fuel, checklist: array_fill_keys(array_keys(config('rentals.checklist')), true),
            signature: $png, signedByName: 'Nur Aisyah Verified', deposit: $deposit);
    }
}
