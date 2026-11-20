<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — pricing & revenue:
 *  - pricing_rules: seasonal (date range) and weekend (day-of-week) price adjustments
 *  - cars.deposit_amount: per-car security deposit override
 *  - booking_deposits: the deposit taken at pick-up and how it was settled
 *  - loyalty_transactions: points ledger (balance = sum of points)
 *  - bookings: points redeemed, their value, and the deposit due
 *
 * bookings.total_price stays "what the customer pays":
 *   rental + addons_amount − discount_amount − points_discount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('kind', 10); // season | weekend
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('days_of_week')->nullable(); // ISO 1 (Mon) … 7 (Sun)
            $table->decimal('adjustment_percent', 6, 2); // +20 = 20% dearer, −15 = 15% cheaper
            $table->string('car_type', 20)->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['kind', 'is_active']);
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('price_per_day');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('points_redeemed')->default(0)->after('addons_amount');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_redeemed');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('points_discount');
        });

        Schema::create('booking_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->string('status', 12)->default('held'); // held | released | applied
            $table->decimal('applied_amount', 10, 2)->default(0);
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('type', 12); // earn | redeem | reversal | adjustment
            $table->string('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['booking_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('booking_deposits');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_discount', 'deposit_amount']);
        });
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });
        Schema::dropIfExists('pricing_rules');
    }
};
