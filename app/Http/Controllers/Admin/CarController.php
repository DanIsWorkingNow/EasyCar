<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Branch;
use Illuminate\Http\Request;

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
        $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'type' => 'required|string',
            'transmission' => 'required|string',
            'branch_id' => 'required|exists:branches,id',
        ]);

        Car::create($request->all());

        return redirect()->route('cars.index')->with('success', 'Car added successfully.');
    }

    public function destroy(Car $car)
    {
        $car->delete();
        return redirect()->route('cars.index')->with('success', 'Car deleted.');
    }
}
