<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Car;
use App\Models\Branch;



class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bangi = Branch::where('name', 'Bandar Baru Bangi')->first();
        $shahAlam = Branch::where('name', 'Shah Alam')->first();

        Car::insert([
            ['model' => 'Perodua Myvi', 'type' => 'Hatchback', 'brand' => 'Perodua', 'transmission' => 'Auto', 'branch_id' => $bangi->id, 'photo' => 'myvi.jpeg'],
            ['model' => 'Toyota Vios', 'type' => 'Sedan', 'brand' => 'Toyota', 'transmission' => 'Manual', 'branch_id' => $shahAlam->id, 'photo' => 'vios.jpeg'],
        ]); //
    }
}
