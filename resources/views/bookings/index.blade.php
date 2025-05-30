@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 1rem 0;">
                🚗 My Bookings
            </h1>
            <p style="color: #6b7280; font-size: 1.1rem; margin: 0 0 1.5rem 0;">Manage your car rental bookings</p>
            
            <a href="{{ route('bookings.create') }}" 
               style="display: inline-block; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;"
               onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.4)'"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.3)'">
                ➕ New Booking
            </a>

            @if(session('success'))
                <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem; border-radius: 15px; margin: 1.5rem 0 0 0; font-weight: 600;">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div style="background: linear-gradient(45deg, #ef4444, #dc2626); color: white; padding: 1rem; border-radius: 15px; margin: 1.5rem 0 0 0; font-weight: 600;">
                    ⚠️ {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Bookings List -->
        @forelse($bookings as $booking)
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1); transition: all 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)'">
                
                <!-- Booking Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f3f4f6;">
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0;">
                            🎫 Booking #{{ $booking->id }}
                        </h2>
                        <p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 1rem;">
                            Created: {{ $booking->created_at->format('M d, Y g:i A') }}
                        </p>
                        <p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 1rem;">
    Total Price: 💰 RM{{ number_format($booking->total_price, 2) }}
</p>

                    </div>
                    
                    <div style="text-align: right;">
                        @php
                            $statusColors = [
                                'pending' => 'background: linear-gradient(45deg, #f59e0b, #d97706); color: white;',
                                'approved' => 'background: linear-gradient(45deg, #10b981, #059669); color: white;',
                                'rejected' => 'background: linear-gradient(45deg, #ef4444, #dc2626); color: white;',
                                'completed' => 'background: linear-gradient(45deg, #6b7280, #4b5563); color: white;'
                            ];
                            $statusIcons = [
                                'pending' => '⏳',
                                'approved' => '✅',
                                'rejected' => '❌',
                                'completed' => '🏁'
                            ];
                        @endphp
                        
                        <span style="{{ $statusColors[$booking->status ?? 'pending'] }} padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; font-size: 0.9rem;">
                            {{ $statusIcons[$booking->status ?? 'pending'] }} {{ ucfirst($booking->status ?? 'Pending') }}
                        </span>
                    </div>
                </div>

                <!-- Rental Period -->
                <div style="background: linear-gradient(145deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px; margin-bottom: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
                        <div>
                            <p style="color: #6b7280; margin: 0; font-weight: 600;">📅 Start Date</p>
                            <p style="font-size: 1.2rem; font-weight: 700; color: #1f2937; margin: 0.5rem 0 0 0;">
                                {{ Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}
                            </p>
                            <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">
                                {{ Carbon\Carbon::parse($booking->start_date)->format('l') }}
                            </p>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <div style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600;">
                                {{ Carbon\Carbon::parse($booking->start_date)->diffInDays(Carbon\Carbon::parse($booking->end_date)) + 1 }} 
                                Day{{ Carbon\Carbon::parse($booking->start_date)->diffInDays(Carbon\Carbon::parse($booking->end_date)) + 1 > 1 ? 's' : '' }}
                            </div>
                        </div>
                        
                        <div>
                            <p style="color: #6b7280; margin: 0; font-weight: 600;">📅 End Date</p>
                            <p style="font-size: 1.2rem; font-weight: 700; color: #1f2937; margin: 0.5rem 0 0 0;">
                                {{ Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}
                            </p>
                            <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">
                                {{ Carbon\Carbon::parse($booking->end_date)->format('l') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Booked Cars -->
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.3rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0;">
                        🚗 Booked Cars ({{ $booking->cars->count() }})
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                        @foreach($booking->cars as $car)
                            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 15px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: all 0.3s ease;"
                                 onmouseover="this.style.borderColor='#667eea'; this.style.transform='translateY(-2px)'"
                                 onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'">
                                
                                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                    <img src="{{ asset('images/cars/' . strtolower(str_replace(' ', '_', $car->model)) . '.jpeg') }}" 
                                         alt="{{ $car->brand }} {{ $car->model }}" 
                                         style="width: 80px; height: 50px; object-fit: cover; border-radius: 10px; margin-right: 1rem;" 
                                         onerror="this.onerror=null;this.src='https://via.placeholder.com/80x50/667eea/ffffff?text=Car';">
                                    
                                    <div>
                                        <h4 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0;">
                                            {{ $car->brand }} {{ $car->model }}
                                        </h4>
                                        <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">
                                            {{ ucfirst($car->transmission) }} • {{ $car->type ?? 'Standard' }}
                                        </p>
                                    </div>
                                </div>
                                
                                @if($car->branch)
                                    <div style="background: #f3f4f6; padding: 0.5rem; border-radius: 8px; text-align: center;">
                                        <span style="color: #6b7280; font-size: 0.9rem; font-weight: 600;">
                                            🏢 {{ $car->branch->name }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; align-items: center; padding-top: 1rem; border-top: 2px solid #f3f4f6;">
                    @if(($booking->status ?? 'pending') === 'pending' && Carbon\Carbon::parse($booking->start_date)->isFuture())
                        <a href="{{ route('bookings.edit', $booking) }}" 
                           style="background: linear-gradient(45deg, #3b82f6, #1d4ed8); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;"
                           onmouseover="this.style.transform='translateY(-2px)'"
                           onmouseout="this.style.transform='translateY(0)'">
                            ✏️ Edit Booking
                        </a>
                    @endif
                    
                    @if(Carbon\Carbon::parse($booking->start_date)->isFuture())
                        <form action="{{ route('bookings.destroy', $booking) }}" 
                              method="POST" 
                              style="display: inline;"
                              onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    style="background: linear-gradient(45deg, #ef4444, #dc2626); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.transform='translateY(-2px)'"
                                    onmouseout="this.style.transform='translateY(0)'">
                                🗑️ Cancel Booking
                            </button>
                        </form>
                    @else
                        <span style="color: #6b7280; font-style: italic;">
                            {{ Carbon\Carbon::parse($booking->start_date)->isPast() ? 'Booking has started' : 'Booking cannot be modified' }}
                        </span>
                    @endif
                    
                    <a href="{{ route('bookings.show', $booking) }}" 
                       style="background: linear-gradient(45deg, #6b7280, #4b5563); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        👁️ View Details
                    </a>
                </div>
            </div>
        @empty
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 4rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                <h2 style="font-size: 1.8rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem 0;">No Bookings Yet</h2>
                <p style="color: #6b7280; font-size: 1.1rem; margin: 0 0 2rem 0;">Ready to start your journey? Book your first car rental!</p>
                
                <a href="{{ route('bookings.create') }}" 
                   style="display: inline-block; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;"
                   onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.3)'">
                    🚀 Make Your First Booking
                </a>
            </div>
        @endforelse
    </div>
</div>

@endsection