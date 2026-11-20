<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver verification (KYC). Licence and ID numbers are encrypted at rest
 * (Eloquent "encrypted" cast); document images live on the private disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('licence_number');
            $table->date('licence_expiry');
            $table->date('date_of_birth');
            $table->string('id_type', 10); // ic | passport
            $table->text('id_number');
            $table->string('phone', 30)->nullable();
            $table->string('licence_front_path');
            $table->string('licence_back_path')->nullable();
            $table->string('id_document_path');
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
