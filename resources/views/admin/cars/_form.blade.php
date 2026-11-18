@php($editing = $car->exists)
<form method="POST" action="{{ $editing ? route('admin.cars.update', $car) : route('admin.cars.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-6">
        <section class="card card-pad space-y-5">
            <h2 class="font-semibold">Vehicle</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="brand" label="Brand" :value="$car->brand" required placeholder="Perodua" />
                <x-field name="model" label="Model" :value="$car->model" required placeholder="Myvi" />
                <x-field name="year" label="Year" type="number" :value="$car->year" min="1990" :max="now()->year + 1" placeholder="{{ now()->year }}" />
            </div>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-field name="type" label="Body type" type="select" :value="$car->type" :options="array_combine(\App\Models\Car::TYPES, \App\Models\Car::TYPES)" required />
                <x-field name="transmission" label="Gearbox" type="select" :value="ucfirst(strtolower((string) $car->transmission))" :options="array_combine(\App\Models\Car::TRANSMISSIONS, \App\Models\Car::TRANSMISSIONS)" required />
                <x-field name="fuel_type" label="Fuel" type="select" :value="$car->fuel_type" :options="array_combine(\App\Models\Car::FUEL_TYPES, \App\Models\Car::FUEL_TYPES)" required />
                <x-field name="seats" label="Seats" type="number" :value="$car->seats" min="2" max="15" required />
            </div>
            <x-field name="description" label="Description" type="textarea" :value="$car->description" rows="4" placeholder="What makes this car great for a trip? Shown on the car page." />
        </section>

        <section class="card card-pad space-y-5">
            <h2 class="font-semibold">Pricing &amp; operations</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="price_per_day" label="Price per day (RM)" type="number" step="0.01" min="1" :value="$car->price_per_day" required />
                <x-field name="plate_number" label="Plate number" :value="$car->plate_number" required placeholder="WXY 1234" class="[&_.input]:font-mono [&_.input]:uppercase" />
                <x-field name="branch_id" label="Branch" type="select" :value="$car->branch_id" :options="$branches->pluck('name', 'id')->all()" placeholder="Choose branch" required />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="status" label="Status" type="select" :value="$car->status ?? 'available'" :options="['available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maintenance (hidden from booking)']" required />
                <label class="flex items-center gap-3 self-end rounded-lg bg-white/[0.03] px-4 py-3 ring-1 ring-line">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="checkbox" @checked(old('is_featured', $car->is_featured))>
                    <span><span class="block text-sm font-medium">Feature on the homepage</span><span class="text-xs text-subtle">Shown in the hero billboard</span></span>
                </label>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card card-pad space-y-5">
            <h2 class="font-semibold">Media</h2>
            @if ($car->photo_url)
                <div class="aspect-[16/10] overflow-hidden rounded-lg bg-ink-700"><x-car-photo :car="$car" /></div>
            @endif
            <x-field name="photo" label="Photo" type="file" accept="image/*" help="JPG/PNG/WebP, up to 4 MB. Landscape, dark background looks best in the billboard." />
        </section>

        <section class="card card-pad space-y-5">
            <div class="flex items-center gap-2"><x-icon name="cube" class="text-brand" /><h2 class="font-semibold">3D showroom</h2></div>
            <div class="flex items-end gap-4">
                <x-field name="color" label="Paint colour" type="color" :value="$car->color ?? $car->paint_color" />
                <p class="pb-2 text-xs text-subtle">Default paint for the 3D model.</p>
            </div>
            <x-field name="model_3d" label="Custom 3D model (.glb)" type="file" accept=".glb,model/gltf-binary" help="Optional. Replaces the generated model. Up to 25 MB; name the paint material 'paint' to keep the colour picker." />
            @if ($car->model_3d)
                <label class="flex items-center gap-2 text-sm text-mute"><input type="checkbox" name="remove_model_3d" value="1" class="checkbox"> Remove the uploaded model</label>
            @endif
        </section>

        <div class="flex gap-2">
            <a href="{{ $editing ? route('admin.cars.show', $car) : route('admin.cars.index') }}" class="btn btn-quiet flex-1">Cancel</a>
            <button class="btn btn-primary flex-1">{{ $editing ? 'Save changes' : 'Add to fleet' }}</button>
        </div>
    </div>
</form>
