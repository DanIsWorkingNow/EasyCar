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
        $cars = Car::where('branch_id', $branchId)->get();

        return view('staff.cars.index', compact('cars'));
    }
}
