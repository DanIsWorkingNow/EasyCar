<?php

namespace App\Http\Controllers\Admin;

use App\Models\Car;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;


class CarController extends Controller
{
    // Show the list of all cars
    public function index()
    {
         if (Gate::denies('admin-staff')) {
        abort(403);
    }
        $cars = Car::all(); // Can later paginate if necessary
        return view('admin.cars.index', compact('cars'));
    }

    // Show the form to create a new car
    public function create()
    {
        $this->authorize('create', Car::class);
        return view('admin.cars.create');
    }

    // Store the newly created car in the database
    public function store(Request $request)
    {
         $this->authorize('create', Car::class);
        // Validation
        $request->validate([
            'brand' => 'required',
            'model' => 'required',
            'type' => 'required',
            'transmission' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handling photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('public/cars');
        }

        // Create car record
        Car::create([
            'brand' => $request->brand,
            'model' => $request->model,
            'type' => $request->type,
            'transmission' => $request->transmission,
            'branch_id' => $request->branch_id,
            'photo' => $photoPath, // Store the file path in the database
        ]);

        return redirect()->route('admin.cars.index')->with('success', 'Car added successfully!');
    }

    // Show the form to edit an existing car
    public function edit(Car $car)
    {
         $this->authorize('update', $car);
        return view('admin.cars.edit', compact('car'));
    }

    // Update the car details
    public function update(Request $request, Car $car)
    {
         $this->authorize('update', $car);
        $request->validate([
            'brand' => 'required',
            'model' => 'required',
            'type' => 'required',
            'transmission' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle photo upload for updating
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($car->photo && Storage::exists($car->photo)) {
                Storage::delete($car->photo);
            }
            // Store new photo
            $photoPath = $request->file('photo')->store('public/cars');
            $car->photo = $photoPath;
        }

        // Update the car record
        $car->update([
            'brand' => $request->brand,
            'model' => $request->model,
            'type' => $request->type,
            'transmission' => $request->transmission,
            'branch_id' => $request->branch_id,
        ]);

        return redirect()->route('admin.cars.index')->with('success', 'Car updated successfully!');
    }

    // Delete the car record
    public function destroy(Car $car)
    {
          $this->authorize('delete', $car);

        // Delete the photo if it exists
        if ($car->photo && Storage::exists($car->photo)) {
            Storage::delete($car->photo);
        }
        
        // Delete the car record
        $car->delete();

        return redirect()->route('admin.cars.index')->with('success', 'Car deleted successfully!');
    }
}
