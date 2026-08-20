<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * SUPERSEDES the CarController shipped in Level 1 Part 2 (which added the
 * missing show/edit/update methods and price_per_day/photo handling). This
 * version adds plate_number validation (FR-CAR-06) to store() and update().
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
            'plate_number' => 'required|string|max:20|unique:cars,plate_number',
            'photo' => 'nullable|image|max:4096',
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
            'plate_number' => 'required|string|max:20|unique:cars,plate_number,' . $car->id,
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
