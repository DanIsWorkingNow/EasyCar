<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(['Perodua', 'Toyota', 'Honda', 'Nissan', 'Proton']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2019, 2025),
            'type' => fake()->randomElement(Car::TYPES),
            'transmission' => fake()->randomElement(Car::TRANSMISSIONS),
            'seats' => 5,
            'fuel_type' => 'Petrol',
            'color' => fake()->randomElement(Car::PAINT_PALETTE),
            'price_per_day' => fake()->randomFloat(2, 45, 165),
            'branch_id' => Branch::factory(),
            'photo' => null,
            'plate_number' => strtoupper(fake()->unique()->bothify('W?? ####')),
            'status' => 'available',
        ];
    }
}
