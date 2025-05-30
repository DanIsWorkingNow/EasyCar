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
        $table->unsignedBigInteger('approved_by')->nullable()->after('status');
        $table->timestamp('approved_at')->nullable()->after('approved_by');
        $table->text('approval_notes')->nullable()->after('approved_at');

        $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropForeign(['approved_by']);
        $table->dropColumn(['approved_by', 'approved_at', 'approval_notes']);
    });
}

};
