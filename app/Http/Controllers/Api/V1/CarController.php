<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * SUPERSEDES the CarController shipped in the API kit — adds plate_number
 * validation (FR-CAR-06) to store() and update(), matching the web
 * Admin\CarController in this same enhancement kit.
 */
class CarController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $cars = Car::with('branch')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->transmission))
            ->when($request->filled('brand'), function ($q) use ($request) {
                $q->whereRaw('LOWER(brand) LIKE ?', ['%' . strtolower($request->brand) . '%']);
            })
            ->paginate(20);

        return CarResource::collection($cars);
    }

    public function available(Request $request, BookingAvailabilityService $availability)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'nullable|string',
            'transmission' => 'nullable|string',
        ]);

        $unavailableIds = $availability->unavailableCarIds($validated['start_date'], $validated['end_date']);

        $cars = Car::with('branch')
            ->whereNotIn('id', $unavailableIds)
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->transmission))
            ->get();

        return CarResource::collection($cars);
    }

    public function show(Car $car)
    {
        return new CarResource($car->load('branch'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Car::class);

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

        $car = Car::create($validated);

        return (new CarResource($car->load('branch')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Car $car)
    {
        $this->authorize('update', $car);

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

        return new CarResource($car->fresh('branch'));
    }

    public function destroy(Car $car)
    {
        $this->authorize('delete', $car);

        if ($car->photo) {
            Storage::disk('public')->delete($car->photo);
        }

        $car->delete();

        return response()->json(['data' => ['message' => 'Car deleted.']]);
    }
}
