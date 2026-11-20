<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('marks confirmed bookings that ended before handovers existed as completed', function () {
    $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

    $user = User::factory()->create();
    $row = fn (string $ref, string $start, string $end, string $status) => [
        'reference' => $ref, 'user_id' => $user->id, 'start_date' => $start, 'end_date' => $end,
        'total_days' => 2, 'status' => $status, 'total_price' => 100, 'payment_status' => 'paid',
        'created_at' => now(), 'updated_at' => now(),
    ];
    DB::table('bookings')->insert([
        $row('EC-PAST0001', now()->subDays(10)->toDateString(), now()->subDays(8)->toDateString(), 'approved'),
        $row('EC-NEXT0001', now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString(), 'approved'),
        $row('EC-REJD0001', now()->subDays(10)->toDateString(), now()->subDays(8)->toDateString(), 'rejected'),
    ]);

    $this->artisan('migrate')->assertSuccessful();

    $past = Booking::where('reference', 'EC-PAST0001')->first();
    expect($past->status)->toBe('completed')
        ->and($past->completed_at->toDateString())->toBe($past->end_date->toDateString())
        ->and(Booking::where('reference', 'EC-NEXT0001')->value('status'))->toBe('approved')
        ->and(Booking::where('reference', 'EC-REJD0001')->value('status'))->toBe('rejected');
});
