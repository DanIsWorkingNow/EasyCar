<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $cars = [
            ['brand' => 'Perodua', 'model' => 'Myvi', 'type' => 'Hatchback', 'transmission' => 'automatic', 'branch_id' => 1, 'price_per_day' => 50.00],
            ['brand' => 'Perodua', 'model' => 'Bezza', 'type' => 'Sedan', 'transmission' => 'manual', 'branch_id' => 1, 'price_per_day' => 45.00],
            ['brand' => 'Toyota', 'model' => 'Vios', 'type' => 'Sedan', 'transmission' => 'automatic', 'branch_id' => 2, 'price_per_day' => 60.00],
            ['brand' => 'Toyota', 'model' => 'Avanza', 'type' => 'MPV', 'transmission' => 'manual', 'branch_id' => 2, 'price_per_day' => 55.00],
            ['brand' => 'Honda', 'model' => 'City', 'type' => 'Sedan', 'transmission' => 'automatic', 'branch_id' => 3, 'price_per_day' => 120.00],
            ['brand' => 'Honda', 'model' => 'CR-V', 'type' => 'SUV', 'transmission' => 'automatic', 'branch_id' => 3, 'price_per_day' => 100.00],
            ['brand' => 'Nissan', 'model' => 'Almera', 'type' => 'Sedan', 'transmission' => 'automatic', 'branch_id' => 1, 'price_per_day' => 110.00],
            ['brand' => 'Nissan', 'model' => 'X-Trail', 'type' => 'SUV', 'transmission' => 'manual', 'branch_id' => 2, 'price_per_day' => 165.00],
            ['brand' => 'Proton', 'model' => 'Saga', 'type' => 'Sedan', 'transmission' => 'manual', 'branch_id' => 3, 'price_per_day' => 60.00],
            ['brand' => 'Proton', 'model' => 'Exora', 'type' => 'MPV', 'transmission' => 'automatic', 'branch_id' => 1, 'price_per_day' => 95.00],
        ];

        foreach ($cars as $car) {
            Car::create($car);
        }
    }
}
