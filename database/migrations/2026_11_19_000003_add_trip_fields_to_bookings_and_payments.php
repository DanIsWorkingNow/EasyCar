<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - bookings: when the trip actually started/ended (set by the handover),
 *   and whether the overdue alert has gone out.
 * - payments.purpose: "booking" (the rental itself) or "charges" (extras
 *   billed after return). Existing rows are rental payments.
 *
 * Backfill: confirmed bookings that ended before handovers were recorded
 * are marked completed (on their end date). Otherwise every historical
 * trip would sit on the operations board as a "no-show" and never count
 * as completed. Revenue and utilisation already treat approved and
 * completed alike, so dashboards are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('picked_up_at')->nullable()->after('cancellation_reason');
            $table->timestamp('returned_at')->nullable()->after('picked_up_at');
            $table->timestamp('completed_at')->nullable()->after('returned_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('completed_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 20)->default('booking')->after('user_id');
        });

        DB::table('bookings')
            ->where('status', 'approved')
            ->whereDate('end_date', '<', now()->toDateString())
            ->whereNull('deleted_at')
            ->update([
                'status' => 'completed',
                'completed_at' => DB::raw('end_date'),
                'returned_at' => DB::raw('end_date'),
            ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['picked_up_at', 'returned_at', 'completed_at', 'overdue_notified_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
