<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin EasyCar',
            'email' => 'admin@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 5,
            'role' => 'admin',
        ]);

        // Staff - assigned to branch_id 1, 2, 3
        User::create([
            'name' => 'Staff Bangi',
            'email' => 'staff.bangi@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 1,
            'role' => 'staff',
            'branch_id' => 1,
        ]);

        User::create([
            'name' => 'Staff Shah Alam',
            'email' => 'staff.shahalam@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 1,
            'role' => 'staff',
            'branch_id' => 2,
        ]);

        User::create([
            'name' => 'Staff Gombak',
            'email' => 'staff.gombak@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 1,
            'role' => 'staff',
            'branch_id' => 3,
        ]);

        // Customers
        User::create([
            'name' => 'Customer 1',
            'email' => 'customer1@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 0,
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'Customer 2',
            'email' => 'customer2@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 0,
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'Customer 3',
            'email' => 'customer3@easycar.com',
            'password' => Hash::make('password'),
            'userLevel' => 0,
            'role' => 'customer',
        ]);
    }
}
