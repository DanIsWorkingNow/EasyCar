<div>
    @if ($errors->has('selectedCarIds'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $errors->first('selectedCarIds') }}
        </div>
    @endif

    {{-- Filter panel — adds start/end date to the existing branch/type/transmission/search filters (FR-BKG-05). --}}
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">🔍 Filter Available Cars</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">📅 Start Date</label>
                <input type="date" wire:model.live="startDate"
                       min="{{ now()->addDays(2)->toDateString() }}"
                       class="w-full border-gray-300 rounded-lg">
                @error('startDate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">📅 End Date</label>
                <input type="date" wire:model.live="endDate"
                       min="{{ $startDate ?? now()->addDays(2)->toDateString() }}"
                       class="w-full border-gray-300 rounded-lg">
                @error('endDate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">🔍 Search Cars</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search by brand, model..." class="w-full border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">🏢 Branch Location</label>
                <select wire:model.live="branchId" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">🚗 Car Type</label>
                <select wire:model.live="type" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Types</option>
                    <option value="Sedan">Sedan</option>
                    <option value="SUV">SUV</option>
                    <option value="Hatchback">Hatchback</option>
                    <option value="MPV">MPV</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">⚙️ Transmission</label>
                <select wire:model.live="transmission" class="w-full border-gray-300 rounded-lg">
                    <option value="">All Transmissions</option>
                    <option value="Automatic">Automatic</option>
                    <option value="Manual">Manual</option>
                </select>
            </div>
        </div>

        <button wire:click="clearFilters" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
            🗑️ Clear All Filters
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Main grid --}}
        <div class="lg:col-span-3 bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">🚗 Select Your Cars</h2>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                    {{ count($selectedCarIds) }}/2 Selected
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse ($cars as $car)
                    @php
                        $isUnavailable = ($startDate && $endDate) && in_array($car->id, $unavailableIds, true);
                        $isSelected = in_array($car->id, $selectedCarIds, true);
                    @endphp
                    <div class="border rounded-xl p-3 relative {{ $isUnavailable ? 'opacity-50' : '' }} {{ $isSelected ? 'ring-2 ring-indigo-500' : '' }}">
                        @if ($car->photo)
                            <img src="{{ Storage::url($car->photo) }}" class="rounded-lg w-full h-32 object-cover mb-2" alt="{{ $car->brand }} {{ $car->model }}">
                        @endif

                        <h3 class="font-bold">{{ $car->brand }} {{ $car->model }}</h3>
                        <p class="text-sm text-gray-500 font-mono">{{ $car->plate_number ?? 'No plate on file' }}</p>
                        <p class="text-sm text-gray-700">💰 RM{{ number_format($car->price_per_day, 2) }} / day</p>

                        <div class="flex flex-wrap gap-1 mt-2">
                            <span class="bg-gray-100 rounded-full px-2 py-0.5 text-xs">⚙️ {{ $car->transmission }}</span>
                            <span class="bg-gray-100 rounded-full px-2 py-0.5 text-xs">🏢 {{ $car->branch->name ?? '' }}</span>
                            <span class="bg-indigo-100 text-indigo-800 rounded-full px-2 py-0.5 text-xs">🚙 {{ $car->type }}</span>
                        </div>

                        @if ($isUnavailable)
                            <div class="mt-2">
                                <span class="bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded-full">
                                    Not available for these dates
                                </span>
                            </div>
                        @else
                            <button wire:click="toggleCar({{ $car->id }})"
                                    class="mt-3 w-full py-1.5 rounded-lg text-sm font-medium {{ $isSelected ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                {{ $isSelected ? '✓ Selected' : 'Select this car' }}
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="col-span-full text-center text-gray-400 py-8">No cars match your filters.</p>
                @endforelse
            </div>
        </div>

        {{-- FR-BKG-08: live side panel, no navigation --}}
        <aside class="bg-white rounded-xl shadow p-6 h-fit sticky top-4">
            <h3 class="font-bold mb-3">📊 Availability</h3>

            @if ($startDate && $endDate)
                <p class="text-3xl font-bold text-indigo-600">{{ $availableCount }}</p>
                <p class="text-sm text-gray-500 mb-4">
                    of {{ $cars->count() }} cars available<br>
                    {{ \Carbon\Carbon::parse($startDate)->format('M d') }} – {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </p>
            @else
                <p class="text-sm text-gray-500 mb-4">Select your dates above to see live availability.</p>
            @endif

            <button wire:click="createBooking" wire:loading.attr="disabled"
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg font-medium disabled:opacity-50">
                <span wire:loading.remove>Complete Booking</span>
                <span wire:loading>Booking...</span>
            </button>
        </aside>
    </div>
</div>
