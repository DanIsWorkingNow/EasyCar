<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's standard notifications table (normally generated via
 * `php artisan notifications:table`) — needed because the Level 3 kit's
 * BookingConfirmed/BookingStatusChanged notifications use the 'database'
 * channel (via($notifiable) => ['mail', 'database']) alongside 'mail'.
 * No prior migration for this existed in the project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
