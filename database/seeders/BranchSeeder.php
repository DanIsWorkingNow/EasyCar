<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Bandar Baru Bangi', 'city' => 'Bangi, Selangor', 'address' => 'Jalan 9/9C, Seksyen 9, 43650 Bandar Baru Bangi', 'phone' => '+60 3-8925 1234'],
            ['name' => 'Shah Alam', 'city' => 'Shah Alam, Selangor', 'address' => 'Persiaran Perbandaran, Seksyen 14, 40000 Shah Alam', 'phone' => '+60 3-5511 2345'],
            ['name' => 'Gombak', 'city' => 'Gombak, Selangor', 'address' => 'Jalan Gombak, Batu 8, 53100 Gombak', 'phone' => '+60 3-6187 3456'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
