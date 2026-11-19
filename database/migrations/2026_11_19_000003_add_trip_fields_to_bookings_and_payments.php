<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - bookings: when the trip actually started/ended (set by the handover),
 *   and whether the overdue alert has gone out.
 * - payments.purpose: "booking" (the rental itself) or "charges" (extras
 *   billed after return). Existing rows are rental payments.
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
