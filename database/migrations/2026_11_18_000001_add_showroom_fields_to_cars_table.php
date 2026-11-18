<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Showroom metadata for the revamped catalogue and the 3D viewer:
 * a paint colour the procedural model renders in, an optional uploaded
 * .glb model that replaces it, and the spec fields the detail page shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('model');
            $table->unsignedTinyInteger('seats')->default(5)->after('transmission');
            $table->string('fuel_type', 20)->default('Petrol')->after('seats');
            $table->string('color', 7)->nullable()->after('fuel_type');
            $table->text('description')->nullable()->after('color');
            $table->string('model_3d')->nullable()->after('photo');
            $table->boolean('is_featured')->default(false)->after('model_3d');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['year', 'seats', 'fuel_type', 'color', 'description', 'model_3d', 'is_featured']);
        });
    }
};
