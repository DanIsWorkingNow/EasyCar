@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">

        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 1rem 0;">
                🚗 Available Cars
            </h1>
            <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">Choose from our premium fleet of vehicles</p>
        </div>

        <!-- Filter Section -->
        <form method="GET" action="{{ route('cars.index') }}" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <div class="row g-3">
                <div class="col-md-3">
                    <div style="position: relative;">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="🔍 Search brand or model" style="border-radius: 15px; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; transition: all 0.3s ease;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="branch_id" class="form-control" style="border-radius: 15px; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; transition: all 0.3s ease;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        <option value="">🏢 All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-control" style="border-radius: 15px; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; transition: all 0.3s ease;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        <option value="">🚙 All Types</option>
                        <option value="Sedan" {{ request('type') == 'Sedan' ? 'selected' : '' }}>Sedan</option>
                        <option value="Hatchback" {{ request('type') == 'Hatchback' ? 'selected' : '' }}>Hatchback</option>
                        <option value="MPV" {{ request('type') == 'MPV' ? 'selected' : '' }}>MPV</option>
                        <option value="SUV" {{ request('type') == 'SUV' ? 'selected' : '' }}>SUV</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="transmission" class="form-control" style="border-radius: 15px; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; transition: all 0.3s ease;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        <option value="">⚙️ All Transmissions</option>
                        <option value="automatic" {{ request('transmission') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        <option value="manual" {{ request('transmission') == 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; padding: 0.75rem 2rem; border-radius: 25px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    🔍 Filter Cars
                </button>
                @if(request()->hasAny(['search', 'branch_id', 'type', 'transmission']))
                    <a href="{{ route('cars.index') }}" style="background: #6b7280; color: white; padding: 0.75rem 2rem; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 1rem; margin-left: 1rem; display: inline-block; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        🔄 Clear Filters
                    </a>
                @endif
            </div>
        </form>

        <!-- Results Summary -->
        @if($cars->count() > 0)
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 1rem 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
                <p style="margin: 0; color: #6b7280; font-weight: 600;">
                    Showing {{ $cars->count() }} car{{ $cars->count() > 1 ? 's' : '' }} available for rental
                </p>
            </div>
        @endif

        <!-- Car Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
            @forelse ($cars as $car)
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); transition: all 0.4s ease; position: relative;"
                     onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.1)'">
                    
                    <!-- Car Image -->
                    <div style="width: 100%; height: 200px; overflow: hidden; position: relative;">
                        <img src="{{ asset('images/cars/' . strtolower(str_replace(' ', '_', $car->model)) . '.jpeg') }}"
                             alt="{{ $car->brand }} {{ $car->model }}"
                             onerror="this.src='https://via.placeholder.com/400x200/667eea/ffffff?text={{ urlencode($car->brand . ' ' . $car->model) }}';"
                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;"
                             onmouseover="this.style.transform='scale(1.1)'"
                             onmouseout="this.style.transform='scale(1)'">
                        
                        <!-- Availability Badge -->
                        <div style="position: absolute; top: 1rem; right: 1rem; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                            ✅ Available
                        </div>
                    </div>

                    <!-- Car Details -->
                    <div style="padding: 2rem;">
                        <!-- Car Name -->
                        <h3 style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0; text-align: center;">
                            {{ $car->brand }} {{ $car->model }}
                        </h3>

                         <!-- Car Price -->
    <p style="text-align: center; font-weight: 600; color: #4b5563; font-size: 1.1rem; margin-top: -0.5rem; margin-bottom: 1rem;">
        💰 RM{{ number_format($car->price_per_day, 2) }} / day
    </p>

                        <!-- Car Specifications -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem;">
                            <div style="background: #f8fafc; padding: 0.75rem; border-radius: 12px; text-align: center; border-left: 4px solid #667eea;">
                                <div style="font-size: 1.2rem; margin-bottom: 0.25rem;">⚙️</div>
                                <div style="font-weight: 600; color: #374151; font-size: 0.9rem;">{{ ucfirst($car->transmission) }}</div>
                            </div>
                            
                            @if ($car->type)
                                <div style="background: #f8fafc; padding: 0.75rem; border-radius: 12px; text-align: center; border-left: 4px solid #8b5cf6;">
                                    <div style="font-size: 1.2rem; margin-bottom: 0.25rem;">🚗</div>
                                    <div style="font-weight: 600; color: #374151; font-size: 0.9rem;">{{ $car->type }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Branch Information -->
                        <div style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); padding: 1rem; border-radius: 15px; text-align: center; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <span style="font-size: 1.2rem;">🏢</span>
                                <span style="font-weight: 600; color: #374151;">{{ $car->branch->name ?? 'N/A' }}</span>
                            </div>
                            @if($car->branch && $car->branch->address)
                                <div style="color: #6b7280; font-size: 0.85rem; margin-top: 0.25rem;">
                                    📍 {{ $car->branch->address }}
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="{{ route('bookings.create', ['car' => $car->id]) }}" 
                               style="flex: 1; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.875rem 1rem; border-radius: 15px; text-decoration: none; font-weight: 600; text-align: center; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);"
                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.3)'"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.2)'">
                                🚀 Book Now
                            </a>
                            
                            <button onclick="viewCarDetails('{{ $car->id }}', '{{ $car->brand }}', '{{ $car->model }}', '{{ $car->type }}', '{{ ucfirst($car->transmission) }}', '{{ $car->branch->name ?? 'N/A' }}')"
                                    style="background: linear-gradient(45deg, #6366f1, #4f46e5); color: white; padding: 0.875rem 1rem; border: none; border-radius: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(99, 102, 241, 0.3)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(99, 102, 241, 0.2)'">
                                👁️
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1;">
                    <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 4rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                        <h2 style="font-size: 1.8rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0;">No Cars Available</h2>
                        <p style="color: #6b7280; font-size: 1.1rem; margin: 0 0 2rem 0;">
                            @if(request()->hasAny(['search', 'branch_id', 'type', 'transmission']))
                                No cars match your filter criteria. Try adjusting your filters.
                            @else
                                Currently no cars are available for rental.
                            @endif
                        </p>
                        
                        @if(request()->hasAny(['search', 'branch_id', 'type', 'transmission']))
                            <a href="{{ route('cars.index') }}" 
                               style="display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 1rem 2rem; border-radius: 25px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;"
                               onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.4)'"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'">
                                🔄 View All Cars
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Car Details Modal -->
<div id="carModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(5px);" onclick="closeCarModal()">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 20px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.2);" onclick="event.stopPropagation()">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0;" id="modalCarName"></h3>
        </div>
        
        <div id="modalCarDetails" style="color: #6b7280; line-height: 1.6;"></div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <button onclick="closeCarModal()" style="background: #6b7280; color: white; padding: 0.75rem 2rem; border: none; border-radius: 15px; font-weight: 600; cursor: pointer; margin-right: 1rem;">
                Close
            </button>
            <button onclick="bookFromModal()" id="modalBookBtn" style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.75rem 2rem; border: none; border-radius: 15px; font-weight: 600; cursor: pointer;">
                Book This Car
            </button>
        </div>
    </div>
</div>

<script>
let selectedCarId = null;

function viewCarDetails(carId, brand, model, type, transmission, branch) {
    selectedCarId = carId;
    document.getElementById('modalCarName').textContent = brand + ' ' + model;
    document.getElementById('modalCarDetails').innerHTML = `
        <div style="display: grid; gap: 1rem;">
            <div><strong>🚗 Vehicle Type:</strong> ${type || 'Standard'}</div>
            <div><strong>⚙️ Transmission:</strong> ${transmission}</div>
            <div><strong>🏢 Branch Location:</strong> ${branch}</div>
            <div><strong>✅ Status:</strong> Available for rental</div>
        </div>
    `;
    document.getElementById('carModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeCarModal() {
    document.getElementById('carModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    selectedCarId = null;
}

function bookFromModal() {
    if (selectedCarId) {
        window.location.href = `{{ route('bookings.create') }}?car=${selectedCarId}`;
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCarModal();
    }
});
</script>
@endsection