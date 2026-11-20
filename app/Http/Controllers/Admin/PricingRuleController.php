<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Car;
use App\Models\PricingRule;
use App\Models\User;
use App\Services\Pricing\LoyaltyService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingRuleController extends Controller
{
    public function index(Request $request, PricingService $pricing)
    {
        $cars = Car::orderBy('brand')->orderBy('model')->get();
        $car = $cars->firstWhere('id', (int) $request->input('car')) ?? $cars->first();

        return view('admin.pricing.index', [
            'rules' => PricingRule::with('branch')->orderByDesc('is_active')->orderBy('kind')->orderByDesc('priority')->get(),
            'cars' => $cars,
            'previewCar' => $car,
            'preview' => $car ? $pricing->dailyRates($car, today(), today()->addDays(34)) : [],
        ]);
    }

    public function create()
    {
        return view('admin.pricing.form', ['rule' => new PricingRule(['kind' => PricingRule::WEEKEND, 'days_of_week' => [6, 7], 'adjustment_percent' => 10, 'is_active' => true]), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        PricingRule::create($this->validated($request));

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule created.');
    }

    public function edit(PricingRule $pricing)
    {
        return view('admin.pricing.form', ['rule' => $pricing, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, PricingRule $pricing)
    {
        $pricing->update($this->validated($request));

        return redirect()->route('admin.pricing.index')->with('success', "{$pricing->name} updated. New bookings use it straight away; existing bookings keep their price.");
    }

    public function destroy(PricingRule $pricing)
    {
        $pricing->delete();

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing rule deleted.');
    }

    public function adjustPoints(Request $request, User $user, LoyaltyService $loyalty)
    {
        $data = $request->validate([
            'points' => 'required|integer|not_in:0|between:-100000,100000',
            'reason' => 'required|string|max:150',
        ]);

        $loyalty->adjust($user, (int) $data['points'], $data['reason'], $request->user());

        return redirect()->to(route('admin.users.edit', $user).'#rewards')->with('success', 'Points adjusted. New balance: '.number_format($user->pointsBalance()).'.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'kind' => ['required', Rule::in([PricingRule::SEASON, PricingRule::WEEKEND])],
            'starts_on' => 'nullable|required_if:kind,season|date',
            'ends_on' => 'nullable|required_if:kind,season|date|after_or_equal:starts_on',
            'days_of_week' => 'nullable|required_if:kind,weekend|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'adjustment_percent' => 'required|numeric|between:-90,300|not_in:0',
            'car_type' => ['nullable', Rule::in(Car::TYPES)],
            'branch_id' => 'nullable|exists:branches,id',
            'priority' => 'nullable|integer|between:-100,100',
            'is_active' => 'boolean',
        ], [
            'starts_on.required_if' => 'A season needs a start date.',
            'ends_on.required_if' => 'A season needs an end date.',
            'days_of_week.required_if' => 'Pick at least one day.',
        ]);

        return array_merge(['is_active' => false, 'priority' => 0, 'car_type' => null, 'branch_id' => null], $data, [
            'days_of_week' => $data['kind'] === PricingRule::WEEKEND ? array_values(array_map('intval', $data['days_of_week'] ?? [])) : null,
        ]);
    }
}
