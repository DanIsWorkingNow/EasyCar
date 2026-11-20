<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extras billed on top of the rental: late return, fuel, mileage,
 * damage, cleaning. Settled online (payments.purpose = charges) or at
 * the counter; staff can void a charge with a reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id', 'paid_at', 'voided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_charges');
    }
};
