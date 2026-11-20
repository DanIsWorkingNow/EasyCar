<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    public function index()
    {
        $promos = PromoCode::withCount(['bookings as redemptions_count' => fn ($q) => $q->whereNotIn('status', Booking::RELEASED_STATUSES)])
            ->withSum(['bookings as discount_given' => fn ($q) => $q->whereNotIn('status', Booking::RELEASED_STATUSES)], 'discount_amount')
            ->orderByDesc('is_active')->orderBy('code')
            ->get();

        return view('admin.promo-codes.index', ['promos' => $promos]);
    }

    public function create()
    {
        return view('admin.promo-codes.form', ['promo' => new PromoCode(['type' => PromoCode::PERCENT, 'per_user_limit' => 1, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        PromoCode::create($this->validated($request));

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code created.');
    }

    public function edit(PromoCode $promoCode)
    {
        return view('admin.promo-codes.form', ['promo' => $promoCode]);
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $promoCode->update($this->validated($request, $promoCode));

        return redirect()->route('admin.promo-codes.index')->with('success', "{$promoCode->code} updated.");
    }

    public function destroy(PromoCode $promoCode)
    {
        // Codes that were used stay for the record; deactivate instead.
        if ($promoCode->bookings()->exists()) {
            $promoCode->update(['is_active' => false]);

            return redirect()->route('admin.promo-codes.index')->with('success', "{$promoCode->code} has been used, so it was deactivated rather than deleted.");
        }

        $promoCode->delete();

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code deleted.');
    }

    private function validated(Request $request, ?PromoCode $promo = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('promo_codes', 'code')->ignore($promo?->id)],
            'description' => 'nullable|string|max:255',
            'type' => ['required', Rule::in([PromoCode::PERCENT, PromoCode::FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', $request->input('type') === PromoCode::PERCENT ? 'max:100' : 'max:100000'],
            'max_discount' => 'nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'max_redemptions' => 'nullable|integer|min:1',
            'per_user_limit' => 'required|integer|min:0|max:100',
            'first_booking_only' => 'boolean',
            'is_active' => 'boolean',
        ], ['code.regex' => 'Use letters, numbers, dashes or underscores only.']);

        return $data + ['first_booking_only' => false, 'is_active' => false];
    }
}
