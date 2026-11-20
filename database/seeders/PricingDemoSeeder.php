<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

/**
 * Demo pricing rules (only seeded with the demo data, never by a migration:
 * prices are a business decision for each install).
 */
class PricingDemoSeeder extends Seeder
{
    public function run(): void
    {
        PricingRule::firstOrCreate(['name' => 'Weekend'], [
            'kind' => PricingRule::WEEKEND, 'days_of_week' => [6, 7], 'adjustment_percent' => 10, 'priority' => 0, 'is_active' => true,
        ]);

        PricingRule::firstOrCreate(['name' => 'Year-end holidays'], [
            'kind' => PricingRule::SEASON, 'starts_on' => now()->year.'-12-20', 'ends_on' => (now()->year + 1).'-01-02',
            'adjustment_percent' => 20, 'priority' => 10, 'is_active' => true,
        ]);

        PricingRule::firstOrCreate(['name' => 'Midweek hatchback deal'], [
            'kind' => PricingRule::WEEKEND, 'days_of_week' => [2, 3], 'adjustment_percent' => -10, 'car_type' => 'Hatchback', 'priority' => 5, 'is_active' => true,
        ]);
    }
}
