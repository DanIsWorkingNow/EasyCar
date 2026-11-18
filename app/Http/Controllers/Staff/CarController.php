<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_id;
        $cars = Car::with('branch')->where('branch_id', $branchId)->orderBy('brand')->orderBy('model')->get();

        return view('staff.cars.index', compact('cars'));
    }
}
