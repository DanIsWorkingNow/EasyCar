<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * FIX (TD-18, TD-19): this controller is bound to Route::resource('/cars', ...),
 * which registers all seven RESTful routes including show/edit/update — but it
 * previously only implemented index(), create(), store(), and destroy(). The
 * admin car list's "Edit" link pointed at a route with no controller method
 * and no view behind it. This version adds the three missing methods, actually
 * validates and saves price_per_day and an uploaded photo (neither of which
 * store() persisted before, since neither was in Car's old $fillable), and
 * fixes both success redirects, which previously pointed at route('cars.index')
 * (the public car-browse page) instead of route('admin.cars.index').
 */
class CarController extends Controller
{
    public function index()
    {
        $cars = Car::with('branch')->get();
        return view('admin.cars.index', compact('cars'));
    }

    public function create()
    {
        $branches = Branch::all();
        return view('admin.cars.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'transmission' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'price_per_day' => 'required|numeric|min:0.01',
            'photo' => 'nullable|image|max:4096', // 4MB
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('cars', 'public');
        }

        Car::create($validated);

        return redirect()->route('admin.cars.index')->with('success', 'Car added successfully.');
    }

    public function show(Car $car)
    {
        $car->load('branch');
        return view('admin.cars.show', compact('car'));
    }

    public function edit(Car $car)
    {
        $branches = Branch::all();
        return view('admin.cars.edit', compact('car', 'branches'));
    }

    public function update(Request $request, Car $car)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'transmission' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'price_per_day' => 'required|numeric|min:0.01',
            'photo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('photo')) {
            if ($car->photo) {
                Storage::disk('public')->delete($car->photo);
            }
            $validated['photo'] = $request->file('photo')->store('cars', 'public');
        }

        $car->update($validated);

        return redirect()->route('admin.cars.index')->with('success', 'Car updated successfully.');
    }

    public function destroy(Car $car)
    {
        if ($car->photo) {
            Storage::disk('public')->delete($car->photo);
        }

        $car->delete();
        return redirect()->route('admin.cars.index')->with('success', 'Car deleted.');
    }
}
