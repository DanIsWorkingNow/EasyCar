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
        $table->integer('quantity')->default(1);  // Add quantity column with a default value of 1
    });
}

public function down()
{
    Schema::table('car_booking', function (Blueprint $table) {
        $table->dropColumn('quantity');
    });
}

};
