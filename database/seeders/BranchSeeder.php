<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Branch;

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
        ]);//
    }
}
