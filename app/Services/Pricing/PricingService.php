<?php

namespace App\Services\Pricing;

use App\Models\Car;
use App\Models\PricingRule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Day-by-day car hire pricing:
 *
 *   day rate = price_per_day × (1 + season %) × (1 + weekend %)
 *
 * For each kind, the matching rule with the highest priority (then the
 * newest) applies, so a "Hari Raya +25%" season can sit on top of a
 * standing "Weekend +10%" rule without the two seasons stacking. The trip
 * total then gets the best long-rental discount it qualifies for
 * (config/pricing.php — weekly / monthly rates).
 */
class PricingService
{
    /** @var Collection<int, PricingRule>|null */
    private ?Collection $rules = null;

    /**
     * @return array<string, array{rate: float, adjustments: array<int, string>}> keyed by Y-m-d
     */
    public function dailyRates(Car $car, string|CarbonInterface $start, string|CarbonInterface $end): array
    {
        $rates = [];
        $base = (float) $car->price_per_day;

        for ($day = CarbonImmutable::parse($start)->startOfDay(), $last = CarbonImmutable::parse($end)->startOfDay(); $day->lte($last); $day = $day->addDay()) {
            $rate = $base;
            $adjustments = [];

            foreach ([PricingRule::SEASON, PricingRule::WEEKEND] as $kind) {
                if ($rule = $this->ruleFor($kind, $car, $day)) {
                    $rate *= 1 + $rule->adjustment_percent / 100;
                    $adjustments[] = $rule->name.' '.$rule->adjustmentLabel();
                }
            }

            $rates[$day->toDateString()] = ['rate' => round($rate, 2), 'adjustments' => $adjustments];
        }

        return $rates;
    }

    /**
     * The price of one car for the trip.
     *
     * @return array{days: int, base: float, subtotal: float, discount_percent: float, discount: float, total: float, notes: array<int, string>}
     */
    public function carPrice(Car $car, string|CarbonInterface $start, string|CarbonInterface $end): array
    {
        $rates = $this->dailyRates($car, $start, $end);
        $days = count($rates);
        $subtotal = round(array_sum(array_column($rates, 'rate')), 2);
        $percent = $this->durationDiscountPercent($days);
        $discount = round($subtotal * $percent / 100, 2);

        $notes = collect($rates)->flatMap(fn ($r) => $r['adjustments'])->countBy()
            ->map(fn ($n, $label) => $label.' ('.$n.' '.str('day')->plural($n).')')
            ->values()->all();
        if ($percent > 0) {
            $notes[] = ($days >= 28 ? 'Monthly' : 'Weekly').' rate −'.rtrim(rtrim(number_format($percent, 2), '0'), '.').'%';
        }

        return [
            'days' => $days,
            'base' => round((float) $car->price_per_day * $days, 2),
            'subtotal' => $subtotal,
            'discount_percent' => $percent,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'notes' => $notes,
        ];
    }

    public function durationDiscountPercent(int $days): float
    {
        $tiers = collect(config('pricing.duration_discounts', []))->sortKeysDesc();

        foreach ($tiers as $minDays => $percent) {
            if ($days >= (int) $minDays) {
                return (float) $percent;
            }
        }

        return 0.0;
    }

    private function ruleFor(string $kind, Car $car, CarbonInterface $day): ?PricingRule
    {
        return $this->rules()
            ->filter(fn (PricingRule $r) => $r->kind === $kind && $r->appliesTo($car, $day))
            ->sortByDesc(fn (PricingRule $r) => [$r->priority, $r->id])
            ->first();
    }

    /** @return Collection<int, PricingRule> */
    private function rules(): Collection
    {
        return $this->rules ??= PricingRule::where('is_active', true)->get();
    }
}
