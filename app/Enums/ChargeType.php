<?php

namespace App\Enums;

enum ChargeType: string
{
    case LateReturn = 'late_return';
    case Fuel = 'fuel';
    case Mileage = 'mileage';
    case Damage = 'damage';
    case Cleaning = 'cleaning';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LateReturn => 'Late return',
            self::Fuel => 'Fuel top-up',
            self::Mileage => 'Excess mileage',
            self::Damage => 'Damage',
            self::Cleaning => 'Cleaning',
            self::Other => 'Other',
        };
    }

    /**
     * Types staff can add by hand. The rest are calculated at return.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect([self::Damage, self::Cleaning, self::Other])
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
