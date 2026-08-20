<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::insert([
            ['name' => 'Bandar Baru Bangi'],
            ['name' => 'Shah Alam'],
            ['name' => 'Gombak'],
        ]); //
    }
}
