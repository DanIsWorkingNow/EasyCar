@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                    ✏️ Edit Your Booking
                </h1>
                <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">Modify your rental dates and car selection</p>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 1rem;">
                <a href="{{ route('bookings.index') }}" 
                   style="display: inline-block; background: linear-gradient(45deg, #3b82f6, #1d4ed8); color: white; padding: 0.75rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)'">
                    ← Back to My Bookings
                </a>
                
                <a href="{{ route('bookings.show', $booking) }}" 
                   style="display: inline-block; background: linear-gradient(45deg, #6b7280, #4b5563); color: white; padding: 0.75rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3); transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(107, 114, 128, 0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(107, 114, 128, 0.3)'">
                    👁️ View Current Details
                </a>
            </div>

            @if(session('success'))
                <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem; border-radius: 10px; margin: 1rem 0; text-alignment: center; font-weight: 600;">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="background: linear-gradient(45deg, #ef4444, #dc2626); color: white; padding: 1rem; border-radius: 10px; margin: 1rem 0;">
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li style="margin: 0.25rem 0;">⚠️ {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Current Booking Info -->
            <div style="background: linear-gradient(145deg, #f0f9ff, #e0f2fe); padding: 1.5rem; border-radius: 15px; margin-top: 1rem;">
                <h3 style="font-size: 1.2rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0; text-align: center;">
                    📋 Current Booking Details
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
                    <div>
                        <p style="color: #6b7280; margin: 0; font-weight: 600;">📅 Current Start Date</p>
                        <p style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0.25rem 0;">
                            {{ Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}
                        </p>
                    </div>
                    <div>
                        <p style="color: #6b7280; margin: 0; font-weight: 600;">📅 Current End Date</p>
                        <p style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0.25rem 0;">
                            {{ Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}
                        </p>
                    </div>
                    <div>
                        <p style="color: #6b7280; margin: 0; font-weight: 600;">🚗 Current Cars</p>
                        <p style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0.25rem 0;">
                            {{ $booking->cars->count() }} Car{{ $booking->cars->count() > 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('bookings.update', $booking) }}" id="editBookingForm">
            @csrf
            @method('PUT')
            
            <!-- Date Selection Section -->
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.8rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0; display: flex; align-items: center;">
                    📅 Update Your Rental Dates
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label for="start_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 1.1rem;">
                            🏁 Start Date
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               value="{{ old('start_date', $booking->start_date) }}"
                               style="width: 100%; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1.1rem; transition: all 0.3s ease; background: white;"
                               required
                               min="{{ date('Y-m-d', strtotime('+2 days')) }}"
                               onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"
                               onchange="updateCarAvailability()">
                    </div>
                    
                    <div>
                        <label for="end_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 1.1rem;">
                            🏁 End Date
                        </label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               value="{{ old('end_date', $booking->end_date) }}"
                               style="width: 100%; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1.1rem; transition: all 0.3s ease; background: white;"
                               required
                               onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"
                               onchange="updateCarAvailability()">
                    </div>
                </div>
            </div>

            <!-- Car Filters Section -->
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.8rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0; display: flex; align-items: center;">
                    🔍 Filter Available Cars
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <!-- Search -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            🔎 Search Cars
                        </label>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search by brand, model..."
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; transition: all 0.3s ease;"
                               onfocus="this.style.borderColor='#667eea'"
                               onblur="this.style.borderColor='#e5e7eb'"
                               oninput="filterCars()">
                    </div>

                    <!-- Branch Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            🏢 Branch Location
                        </label>
                        <select id="branchFilter" 
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; transition: all 0.3s ease; background: white;"
                                onfocus="this.style.borderColor='#667eea'"
                                onblur="this.style.borderColor='#e5e7eb'"
                                onchange="filterCars()">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Car Type Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            🚙 Car Type
                        </label>
                        <select id="typeFilter" 
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; transition: all 0.3s ease; background: white;"
                                onfocus="this.style.borderColor='#667eea'"
                                onblur="this.style.borderColor='#e5e7eb'"
                                onchange="filterCars()">
                            <option value="">All Types</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="MPV">MPV</option>
                        </select>
                    </div>

                    <!-- Transmission Filter -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            ⚙️ Transmission
                        </label>
                        <select id="transmissionFilter" 
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 10px; transition: all 0.3s ease; background: white;"
                                onfocus="this.style.borderColor='#667eea'"
                                onblur="this.style.borderColor='#e5e7eb'"
                                onchange="filterCars()">
                            <option value="">All Transmissions</option>
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <button type="button" 
                        onclick="clearFilters()"
                        style="background: linear-gradient(45deg, #6b7280, #4b5563); color: white; padding: 0.5rem 1rem; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                        onmouseover="this.style.transform='scale(1.05)'"
                        onmouseout="this.style.transform='scale(1)'">
                    🗑️ Clear All Filters
                </button>
            </div>

            <!-- Car Selection Section -->
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <h2 style="font-size: 1.8rem; font-weight: 700; color: #1f2937; margin: 0 0 1.5rem 0; display: flex; align-items: center; justify-content: space-between;">
                    🚗 Update Your Car Selection 
                    <span id="selectedCount" style="font-size: 1rem; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.5rem 1rem; border-radius: 25px;">0/2 Selected</span>
                </h2>
                
                <div id="carsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    @foreach ($cars as $car)
                        @php
                            $isCurrentlySelected = $booking->cars->contains('id', $car->id);
                        @endphp
                        <div class="car-card {{ $isCurrentlySelected ? 'currently-selected' : '' }}" 
                             data-brand="{{ strtolower($car->brand) }}"
                             data-model="{{ strtolower($car->model) }}"
                             data-type="{{ strtolower($car->type ?? '') }}"
                             data-transmission="{{ strtolower($car->transmission) }}"
                             data-branch="{{ strtolower($car->branch->name ?? '') }}"
                             style="background: {{ $isCurrentlySelected ? 'linear-gradient(145deg, #f0fdf4, #dcfce7)' : 'white' }}; border: 3px solid {{ $isCurrentlySelected ? '#10b981' : '#e5e7eb' }}; border-radius: 20px; padding: 1.5rem; transition: all 0.3s ease; cursor: pointer; position: relative; overflow: hidden;"
                             onclick="toggleCarSelection({{ $car->id }}, this)"
                             onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 30px rgba(0,0,0,0.15)'"
                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'">
                            
                            <!-- Currently Selected Badge -->
                            @if($isCurrentlySelected)
                                <div style="position: absolute; top: 15px; left: 15px; background: linear-gradient(45deg, #3b82f6, #1d4ed8); color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                                    📌 Currently Selected
                                </div>
                            @endif
                            
                            <!-- Car Image -->
                            <div style="text-align: center; margin-bottom: 1rem;">
                                <img src="{{ asset('images/cars/' . strtolower(str_replace(' ', '_', $car->model)) . '.jpeg') }}" 
                                     alt="{{ $car->brand }} {{ $car->model }}" 
                                     style="width: 100%; height: 150px; object-fit: cover; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" 
                                     onerror="this.onerror=null;this.src='https://via.placeholder.com/300x150/667eea/ffffff?text={{ urlencode($car->brand . ' ' . $car->model) }}';">
                            </div>
                            
                            <!-- Car Details -->
                            <div style="text-align: center;">
                                <h3 style="font-size: 1.3rem; font-weight: 700; color: #1f2937; margin: 0 0 0.5rem 0;">
                                    {{ $car->brand }} {{ $car->model }}
                                </h3>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.9rem; color: #6b7280;">
                                        ⚙️ {{ ucfirst($car->transmission) }}
                                    </span>
                                    <span style="background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.9rem; color: #6b7280;">
                                        🏢 {{ $car->branch->name ?? 'N/A' }}
                                    </span>
                                </div>
                                @if($car->type)
                                    <span style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.9rem;">
                                        🚙 {{ $car->type }}
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Selection Indicator -->
                            <div class="selection-indicator" 
                                 style="position: absolute; top: 15px; right: 15px; width: 30px; height: 30px; border-radius: 50%; background: {{ $isCurrentlySelected ? '#10b981' : '#e5e7eb' }}; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                <span style="font-size: 1.2rem; opacity: {{ $isCurrentlySelected ? '1' : '0' }}; color: white;">✓</span>
                            </div>
                            
                            <!-- Hidden Checkbox -->
                            <input type="checkbox" 
                                   name="cars[]" 
                                   value="{{ $car->id }}" 
                                   style="display: none;" 
                                   class="car-checkbox"
                                   {{ $isCurrentlySelected ? 'checked' : '' }}>
                        </div>
                    @endforeach
                </div>
                
                <div id="noResultsMessage" style="text-align: center; padding: 3rem; color: #6b7280; font-size: 1.2rem; display: none;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">😔</div>
                    <p>No cars match your current filters. Try adjusting your search criteria.</p>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="text-align: center; display: flex; gap: 1rem; justify-content: center;">
                <a href="{{ route('bookings.index') }}" 
                   style="background: linear-gradient(45deg, #6b7280, #4b5563); color: white; padding: 1rem 2rem; border-radius: 50px; font-size: 1.1rem; font-weight: 700; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(107, 114, 128, 0.3);"
                   onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(107, 114, 128, 0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(107, 114, 128, 0.3)'">
                    ❌ Cancel Changes
                </a>
                
                <button type="submit" 
                        id="submitBtn"
                        style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white; padding: 1rem 3rem; border: none; border-radius: 50px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3); opacity: 0.5;"
                        disabled
                        onmouseover="if(!this.disabled) { this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(245, 158, 11, 0.4)' }"
                        onmouseout="if(!this.disabled) { this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.3)' }">
                    💾 Update Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedCars = [];
const maxCars = 2;

// Initialize with currently selected cars
document.addEventListener('DOMContentLoaded', function() {
    const currentlySelectedCards = document.querySelectorAll('.car-card.currently-selected');
    currentlySelectedCards.forEach(card => {
        const checkbox = card.querySelector('.car-checkbox');
        if (checkbox && checkbox.checked) {
            selectedCars.push(parseInt(checkbox.value));
        }
    });
    updateUI();
});

function toggleCarSelection(carId, cardElement) {
    const checkbox = cardElement.querySelector('.car-checkbox');
    const indicator = cardElement.querySelector('.selection-indicator');
    const checkmark = indicator.querySelector('span');
    
    if (selectedCars.includes(carId)) {
        // Deselect
        selectedCars = selectedCars.filter(id => id !== carId);
        checkbox.checked = false;
        cardElement.style.borderColor = '#e5e7eb';
        cardElement.style.background = 'white';
        cardElement.classList.remove('currently-selected');
        indicator.style.background = '#e5e7eb';
        checkmark.style.opacity = '0';
        
        // Remove currently selected badge if it exists
        const badge = cardElement.querySelector('.currently-selected-badge');
        if (badge) {
            badge.remove();
        }
    } else if (selectedCars.length < maxCars) {
        // Select
        selectedCars.push(carId);
        checkbox.checked = true;
        cardElement.style.borderColor = '#10b981';
        cardElement.style.background = 'linear-gradient(145deg, #f0fdf4, #dcfce7)';
        cardElement.classList.add('currently-selected');
        indicator.style.background = '#10b981';
        checkmark.style.opacity = '1';
        checkmark.style.color = 'white';
    } else {
        // Max reached
        alert('You can only select maximum 2 cars per booking!');
        return;
    }
    
    updateUI();
}

function updateUI() {
    const selectedCount = document.getElementById('selectedCount');
    const submitBtn = document.getElementById('submitBtn');
    
    selectedCount.textContent = `${selectedCars.length}/2 Selected`;
    
    if (selectedCars.length > 0) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
    }
}

function filterCars() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const branchFilter = document.getElementById('branchFilter').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    const transmissionFilter = document.getElementById('transmissionFilter').value.toLowerCase();
    
    const carCards = document.querySelectorAll('.car-card');
    let visibleCount = 0;
    
    carCards.forEach(card => {
        const brand = card.dataset.brand;
        const model = card.dataset.model;
        const type = card.dataset.type;
        const transmission = card.dataset.transmission;
        const branch = card.dataset.branch;
        
        const matchesSearch = !searchTerm || brand.includes(searchTerm) || model.includes(searchTerm);
        const matchesBranch = !branchFilter || branch === branchFilter;
        const matchesType = !typeFilter || type === typeFilter.toLowerCase();
        const matchesTransmission = !transmissionFilter || transmission === transmissionFilter;
        
        if (matchesSearch && matchesBranch && matchesType && matchesTransmission) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('noResultsMessage').style.display = visibleCount === 0 ? 'block' : 'none';
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('branchFilter').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('transmissionFilter').value = '';
    filterCars();
}

function updateCarAvailability() {
    // This would typically make an AJAX call to check car availability
    // For now, we'll show all cars but could filter out unavailable ones
    console.log('Checking car availability for updated dates...');
}

// Set minimum end date when start date changes
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    const minEndDate = new Date(startDate);
    minEndDate.setDate(startDate.getDate() + 1);
    
    document.getElementById('end_date').min = minEndDate.toISOString().split('T')[0];
});

// Prevent form submission if no cars selected
document.getElementById('editBookingForm').addEventListener('submit', function(e) {
    if (selectedCars.length === 0) {
        e.preventDefault();
        alert('Please select at least one car for your booking.');
        return false;
    }
});
</script>
@endsection