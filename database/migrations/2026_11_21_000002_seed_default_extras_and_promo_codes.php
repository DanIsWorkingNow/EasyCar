<?php

use Database\Seeders\CustomerExperienceSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Data migration: adds the default rental extras (CDW protection, child
 * seat, GPS, additional driver) and the WELCOME10 / WEEKEND20 promo codes,
 * so existing installs get them with `php artisan migrate` and no separate
 * seeding step. Missing rows only; admin edits are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new CustomerExperienceSeeder)->run();
    }

    public function down(): void
    {
        // Left in place on rollback: by then the rows may be on bookings or
        // edited by an admin. Drop them via Admin → Extras / Promotions.
    }
};
