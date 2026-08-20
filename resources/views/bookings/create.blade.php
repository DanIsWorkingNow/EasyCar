@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div style="max-width: 1400px; margin: 0 auto; padding: 0 1rem;">

        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                    🚗 Book Your Perfect Ride
                </h1>
                <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">Select your dates and find the perfect car for your journey — availability updates live as you filter.</p>
            </div>

            <a href="{{ route('bookings.index') }}"
               style="display: inline-block; background: linear-gradient(45deg, #3b82f6, #1d4ed8); color: white; padding: 0.75rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); transition: all 0.3s ease;"
               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.4)'"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)'">
                ← Back to My Bookings
            </a>

            @if(session('success'))
                <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem; border-radius: 10px; margin: 1rem 0 0 0; text-align: center; font-weight: 600;">
                    ✅ {{ session('success') }}
                </div>
            @endif
        </div>

        {{-- Filters, live availability grid, and submission now all live inside this
             component — see CarAvailabilityPicker for the FR-BKG-05..09 implementation. --}}
        <livewire:booking.car-availability-picker />
    </div>
</div>
@endsection
