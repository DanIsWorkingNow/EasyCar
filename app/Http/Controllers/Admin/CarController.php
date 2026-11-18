<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $cars = Car::with('branch')
            ->withCount('bookings')
            ->search($request->input('search'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('brand')->orderBy('model')
            ->get();

        return view('admin.cars.index', [
            'cars' => $cars,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.cars.create', ['car' => new Car(['seats' => 5, 'fuel_type' => 'Petrol', 'status' => 'available']), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated = $this->storeUploads($request, $validated);

        $car = Car::create($validated);
        Cache::forget('landing:cars');

        return redirect()->route('admin.cars.show', $car)->with('success', "{$car->name} added to the fleet.");
    }

    public function show(Car $car)
    {
        $car->load(['branch', 'bookings' => fn ($q) => $q->with('user')->latest('bookings.created_at')->limit(10)]);

        return view('admin.cars.show', compact('car'));
    }

    public function edit(Car $car)
    {
        return view('admin.cars.edit', ['car' => $car, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, Car $car)
    {
        $validated = $this->validated($request, $car);
        $validated = $this->storeUploads($request, $validated, $car);

        if (array_key_exists('status', $validated) && $validated['status'] !== $car->status) {
            $validated['status_changed_at'] = now();
        }

        $car->forceFill($validated)->save();
        Cache::forget('landing:cars');

        return redirect()->route('admin.cars.show', $car)->with('success', 'Car updated.');
    }

    public function destroy(Car $car)
    {
        if ($car->bookings()->whereIn('bookings.status', ['pending', 'approved'])->exists()) {
            return back()->with('error', 'This car has active bookings. Set it to maintenance instead of deleting it.');
        }

        foreach (['photo', 'model_3d'] as $field) {
            if ($car->{$field}) {
                Storage::disk('public')->delete($car->{$field});
            }
        }

        $car->delete();
        Cache::forget('landing:cars');

        return redirect()->route('admin.cars.index')->with('success', 'Car removed from the fleet.');
    }

    private function validated(Request $request, ?Car $car = null): array
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1990|max:'.(now()->year + 1),
            'type' => ['required', Rule::in(Car::TYPES)],
            'transmission' => ['required', Rule::in(Car::TRANSMISSIONS)],
            'seats' => 'required|integer|min:2|max:15',
            'fuel_type' => ['required', Rule::in(Car::FUEL_TYPES)],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => 'nullable|string|max:2000',
            'branch_id' => 'required|exists:branches,id',
            'price_per_day' => 'required|numeric|min:1|max:99999',
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('cars', 'plate_number')->ignore($car?->id)],
            'status' => ['required', Rule::in(['available', 'rented', 'maintenance'])],
            'is_featured' => 'sometimes|boolean',
            'photo' => 'nullable|image|max:4096',
            // .glb (binary glTF) — browsers report various mimetypes, so
            // validate the extension plus a size ceiling.
            'model_3d' => 'nullable|file|extensions:glb|max:25600',
            'remove_model_3d' => 'sometimes|boolean',
        ]);

        $validated['plate_number'] = strtoupper(trim($validated['plate_number']));
        $validated['is_featured'] = $request->boolean('is_featured');

        return $validated;
    }

    private function storeUploads(Request $request, array $validated, ?Car $car = null): array
    {
        unset($validated['photo'], $validated['model_3d'], $validated['remove_model_3d']);

        if ($request->hasFile('photo')) {
            if ($car?->photo) {
                Storage::disk('public')->delete($car->photo);
            }
            $validated['photo'] = $request->file('photo')->store('cars', 'public');
        }

        if ($request->boolean('remove_model_3d') && $car?->model_3d) {
            Storage::disk('public')->delete($car->model_3d);
            $validated['model_3d'] = null;
        }

        if ($request->hasFile('model_3d')) {
            if ($car?->model_3d) {
                Storage::disk('public')->delete($car->model_3d);
            }
            $validated['model_3d'] = $request->file('model_3d')->storeAs(
                'cars/models',
                uniqid('car_', true).'.glb',
                'public'
            );
        }

        return $validated;
    }
}
