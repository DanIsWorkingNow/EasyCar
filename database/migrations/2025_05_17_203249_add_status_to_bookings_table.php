<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add status fields for booking approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])
                ->default('pending')
                ->after('total_days');

            // Add admin approval fields
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('approval_notes');

            // Add foreign key constraint
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes for better performance
            $table->index('status');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_by']);
            $table->dropColumn([
                'status',
                'approved_by',
                'approved_at',
                'approval_notes',
                'rejection_reason',
            ]);
        });
    }
};
