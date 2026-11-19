<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One pickup and one return handover per car per booking. Records the
 * condition the car left and came back in — the evidence behind any
 * fuel, mileage, late or damage charge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // pickup | return
            $table->unsignedInteger('odometer_km');
            $table->unsignedTinyInteger('fuel_level'); // eighths of a tank, 0–8
            $table->json('checklist')->nullable();
            $table->json('damages')->nullable(); // { zone: kind }
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->boolean('needs_maintenance')->default(false);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->unique(['booking_id', 'car_id', 'type']);
            $table->index(['car_id', 'performed_at']);
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('vehicle_inspections');
    }
};
