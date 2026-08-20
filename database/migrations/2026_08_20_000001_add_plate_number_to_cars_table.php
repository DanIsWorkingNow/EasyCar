<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-CAR-06 (TSD Section 5.7.3). Nullable initially so existing seeded rows
 * don't fail the migration — run BackfillCarPlateNumbers afterward, then a
 * follow-up migration can make this column not-null once every row has a
 * value (not included here, so this stays safe to run immediately).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('plate_number')->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('plate_number');
        });
    }
};
