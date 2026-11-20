<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * Default rental extras and two launch promo codes.
 *
 * Runs automatically from the 2026_11_21_000002 migration, so existing
 * installs get them on `php artisan migrate` (the deploy script runs it).
 * Only creates rows that are missing — it never overwrites an extra or
 * code an admin has since edited, and is safe to run again.
 */
class CustomerExperienceSeeder extends Seeder
{
    public function run(): void
    {
        $addons = [
            ['name' => 'Full protection (CDW)', 'description' => 'Reduces your damage excess to RM 0.', 'icon' => 'shield', 'pricing' => Addon::PER_DAY, 'price' => 25, 'max_quantity' => 1, 'sort_order' => 1],
            ['name' => 'Child seat', 'description' => 'ISOFIX seat for children 9–18 kg.', 'icon' => 'users', 'pricing' => Addon::PER_DAY, 'price' => 10, 'max_quantity' => 2, 'sort_order' => 2],
            ['name' => 'GPS navigator', 'description' => 'Offline maps for Peninsular Malaysia.', 'icon' => 'map-pin', 'pricing' => Addon::PER_DAY, 'price' => 8, 'max_quantity' => 1, 'sort_order' => 3],
            ['name' => 'Additional driver', 'description' => 'Add a second verified driver to the agreement.', 'icon' => 'user', 'pricing' => Addon::PER_BOOKING, 'price' => 15, 'max_quantity' => 1, 'sort_order' => 4],
        ];

        foreach ($addons as $addon) {
            Addon::firstOrCreate(['name' => $addon['name']], $addon);
        }

        PromoCode::firstOrCreate(['code' => 'WELCOME10'], [
            'description' => '10% off your first booking',
            'type' => PromoCode::PERCENT, 'value' => 10, 'max_discount' => 100,
            'per_user_limit' => 1, 'first_booking_only' => true, 'is_active' => true,
        ]);

        PromoCode::firstOrCreate(['code' => 'WEEKEND20'], [
            'description' => 'RM 20 off bookings over RM 200',
            'type' => PromoCode::FIXED, 'value' => 20, 'min_spend' => 200,
            'per_user_limit' => 3, 'is_active' => true,
        ]);
    }
}
