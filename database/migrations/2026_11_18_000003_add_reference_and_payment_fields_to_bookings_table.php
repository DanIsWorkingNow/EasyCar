<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - reference: a short, customer-facing booking code (e.g. EC-7KQ2M9XA)
 *   used in emails, receipts and payment gateway descriptions instead of
 *   the sequential primary key.
 * - payment_status: denormalised from the payments table so lists and
 *   dashboards can filter on it without a join.
 * - cancelled_at / cancellation_reason: customer cancellations now set
 *   status=cancelled (and trigger a refund when paid) instead of silently
 *   soft-deleting the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->unique()->after('id');
            $table->string('payment_status', 20)->default('unpaid')->index()->after('total_price');
            $table->timestamp('cancelled_at')->nullable()->after('rejection_reason');
            $table->string('cancellation_reason', 500)->nullable()->after('cancelled_at');
        });

        DB::table('bookings')->whereNull('reference')->orderBy('id')->chunkById(200, function ($bookings) {
            foreach ($bookings as $booking) {
                DB::table('bookings')->where('id', $booking->id)->update([
                    'reference' => Booking::generateReference(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['reference', 'payment_status', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
