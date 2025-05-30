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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer'); // roles: admin, staff, customer
            $table->tinyInteger('userLevel')->default(0); // 0 = customer, 1 = staff, 5 = admin
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropColumn('userLevel');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
