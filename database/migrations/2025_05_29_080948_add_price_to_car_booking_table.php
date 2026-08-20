<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('car_booking', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->default(0);  // Add price column with a default value of 0
        });
    }

    public function down()
    {
        Schema::table('car_booking', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
