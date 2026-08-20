<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-CAR-04. Adds a fleet status field so the dashboard (Level 2) and future
 * reporting can distinguish available/rented/maintenance, and a timestamp to
 * measure how long a car has sat in its current status (downtime tracking).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available')->after('price_per_day');
            $table->timestamp('status_changed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_changed_at']);
        });
    }
};
