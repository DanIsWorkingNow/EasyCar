<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Bandar Baru Bangi', 'Shah Alam', 'Gombak']).' '.fake()->unique()->numberBetween(1, 9999),
        ];
    }
}
