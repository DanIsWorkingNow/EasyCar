<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-USR-05. A small, purpose-built audit table rather than pulling in a
 * general activity-log package — the only thing this needs to record is
 * "who changed whose role, from what, to what, when."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // whose role changed
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete(); // who made the change
            $table->string('old_role')->nullable();
            $table->string('new_role');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_audit_logs');
    }
};
