<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\BookingAddon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddonController extends Controller
{
    public const ICONS = ['shield', 'users', 'user', 'map-pin', 'sparkles', 'bolt', 'phone', 'car', 'clock', 'heart'];

    public function index()
    {
        return view('admin.addons.index', [
            'addons' => Addon::withCount('bookingAddons')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.addons.form', ['addon' => new Addon(['pricing' => Addon::PER_DAY, 'max_quantity' => 1, 'is_active' => true, 'icon' => 'sparkles'])]);
    }

    public function store(Request $request)
    {
        Addon::create($this->validated($request));

        return redirect()->route('admin.addons.index')->with('success', 'Extra created.');
    }

    public function edit(Addon $addon)
    {
        return view('admin.addons.form', compact('addon'));
    }

    public function update(Request $request, Addon $addon)
    {
        // Price changes apply to new bookings only — booking_addons keeps a snapshot.
        $addon->update($this->validated($request));

        return redirect()->route('admin.addons.index')->with('success', "{$addon->name} updated.");
    }

    public function destroy(Addon $addon)
    {
        if (BookingAddon::where('addon_id', $addon->id)->exists()) {
            $addon->update(['is_active' => false]);

            return redirect()->route('admin.addons.index')->with('success', "{$addon->name} is on past bookings, so it was hidden rather than deleted.");
        }

        $addon->delete();

        return redirect()->route('admin.addons.index')->with('success', 'Extra deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:255',
            'icon' => ['required', Rule::in(self::ICONS)],
            'pricing' => ['required', Rule::in([Addon::PER_DAY, Addon::PER_BOOKING])],
            'price' => 'required|numeric|min:0|max:10000',
            'max_quantity' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'boolean',
        ]) + ['is_active' => false, 'sort_order' => 0];
    }
}
