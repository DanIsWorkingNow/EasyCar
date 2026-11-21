<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base data for any environment. Demo bookings/payments are opt-in:
     *   php artisan db:seed --class=Database\\Seeders\\BookingDemoSeeder
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            CarSeeder::class,
            UserSeeder::class,
            RolesAndPermissionsSeeder::class,
            CustomerExperienceSeeder::class,
            PricingDemoSeeder::class,
        ]);
    }
}
