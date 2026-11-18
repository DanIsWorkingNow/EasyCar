<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::pluck('id', 'name');
        $bangi = $branch['Bandar Baru Bangi'] ?? 1;
        $shahAlam = $branch['Shah Alam'] ?? 2;
        $gombak = $branch['Gombak'] ?? 3;

        $cars = [
            ['brand' => 'Perodua', 'model' => 'Myvi', 'year' => 2024, 'type' => 'Hatchback', 'transmission' => 'Automatic', 'seats' => 5, 'color' => '#c1121f', 'branch_id' => $bangi, 'price_per_day' => 50, 'plate_number' => 'BMV 1024',
                'description' => "Malaysia's favourite hatchback — nimble in city traffic, easy to park and famously frugal on fuel."],
            ['brand' => 'Perodua', 'model' => 'Bezza', 'year' => 2023, 'type' => 'Sedan', 'transmission' => 'Manual', 'seats' => 5, 'color' => '#9ca3af', 'branch_id' => $bangi, 'price_per_day' => 45, 'plate_number' => 'BQB 2231',
                'description' => 'A roomy, economical sedan with a generous boot — perfect for balik kampung on a budget.'],
            ['brand' => 'Toyota', 'model' => 'Vios', 'year' => 2024, 'type' => 'Sedan', 'transmission' => 'Automatic', 'seats' => 5, 'color' => '#f5f5f5', 'branch_id' => $shahAlam, 'price_per_day' => 60, 'plate_number' => 'WVS 8830',
                'description' => 'Smooth, quiet and dependable. The Vios is a comfortable cruiser for business trips and highway runs.'],
            ['brand' => 'Toyota', 'model' => 'Avanza', 'year' => 2023, 'type' => 'MPV', 'transmission' => 'Manual', 'seats' => 7, 'color' => '#1f2937', 'branch_id' => $shahAlam, 'price_per_day' => 55, 'plate_number' => 'WAV 5512',
                'description' => 'Seven seats, flexible cargo space and Toyota reliability for the whole family.'],
            ['brand' => 'Honda', 'model' => 'City', 'year' => 2024, 'type' => 'Sedan', 'transmission' => 'Automatic', 'seats' => 5, 'color' => '#1d4ed8', 'branch_id' => $gombak, 'price_per_day' => 120, 'plate_number' => 'VHC 7781',
                'description' => 'Refined and well-equipped with Honda Sensing safety — the executive choice in the B-segment.'],
            ['brand' => 'Honda', 'model' => 'CR-V', 'year' => 2024, 'type' => 'SUV', 'transmission' => 'Automatic', 'seats' => 5, 'color' => '#7c2d12', 'branch_id' => $gombak, 'price_per_day' => 100, 'plate_number' => 'VCR 3409',
                'description' => 'A spacious premium SUV with a commanding view of the road and room for everyone’s luggage.'],
            ['brand' => 'Nissan', 'model' => 'Almera', 'year' => 2024, 'type' => 'Sedan', 'transmission' => 'Automatic', 'seats' => 5, 'color' => '#3f3f1f', 'branch_id' => $bangi, 'price_per_day' => 110, 'plate_number' => 'BAL 6620',
                'description' => 'Turbocharged and sharply styled, the Almera Black Series turns every commute into an occasion.'],
            ['brand' => 'Nissan', 'model' => 'X-Trail', 'year' => 2024, 'type' => 'SUV', 'transmission' => 'Automatic', 'seats' => 7, 'color' => '#c0c4c8', 'branch_id' => $shahAlam, 'price_per_day' => 165, 'plate_number' => 'WXT 9001', 'is_featured' => true,
                'description' => 'Seven seats, intelligent all-wheel drive and a cabin built for long weekends. Our flagship SUV for road trips to the highlands and beyond.'],
            ['brand' => 'Proton', 'model' => 'Saga', 'year' => 2023, 'type' => 'Sedan', 'transmission' => 'Manual', 'seats' => 5, 'color' => '#e5e7eb', 'branch_id' => $gombak, 'price_per_day' => 60, 'plate_number' => 'VSG 1185',
                'description' => 'The honest, hard-working sedan that keeps costs low and gets you there every time.'],
            ['brand' => 'Proton', 'model' => 'Exora', 'year' => 2023, 'type' => 'MPV', 'transmission' => 'Automatic', 'seats' => 7, 'color' => '#0f766e', 'branch_id' => $bangi, 'price_per_day' => 95, 'plate_number' => 'BEX 4477',
                'description' => 'A practical seven-seater MPV with flexible seating, ideal for family outings and group trips.'],
        ];

        foreach ($cars as $car) {
            Car::updateOrCreate(['plate_number' => $car['plate_number']], $car + ['fuel_type' => 'Petrol', 'status' => 'available']);
        }
    }
}
