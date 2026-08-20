<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;

/**
 * TSD Section 5.7.4. Run once after the plate_number migration, and safe to
 * re-run any time (only touches rows where plate_number is still null) —
 * e.g. after re-seeding a fresh database in a new environment.
 */
class BackfillCarPlateNumbers extends Command
{
    protected $signature = 'cars:backfill-plate-numbers';

    protected $description = 'Assign a plate number to any car that does not have one yet';

    public function handle(): int
    {
        $cars = Car::whereNull('plate_number')->orderBy('id')->get();

        if ($cars->isEmpty()) {
            $this->info('Every car already has a plate number — nothing to do.');

            return self::SUCCESS;
        }

        $letters = range('A', 'Z');

        $cars->each(function (Car $car, int $i) use ($letters) {
            $letter = $letters[$i % 26];
            $number = 1000 + $i;
            $car->update(['plate_number' => "W{$letter} {$number}"]);
        });

        $this->info("Assigned plate numbers to {$cars->count()} car(s).");

        return self::SUCCESS;
    }
}
