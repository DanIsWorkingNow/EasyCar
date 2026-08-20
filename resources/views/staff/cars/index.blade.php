@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1;"></div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="font-size: 3rem;">🚗</div>
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">
                        Branch Cars
                    </h1>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">
                        Fleet for <span style="font-weight: 600; color: #1f2937;">{{ Auth::user()->branch->name ?? 'your branch' }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Cars Table -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);">
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Car</th>
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Plate Number</th>
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Type</th>
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Transmission</th>
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Price / Day</th>
                            <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cars as $car)
                            @php
                                $statusColors = [
                                    'available' => ['bg' => '#d1fae5', 'text' => '#047857'],
                                    'rented' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                                    'maintenance' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                                ];
                                $sc = $statusColors[$car->status ?? 'available'] ?? $statusColors['available'];
                            @endphp
                            <tr style="border-top: 1px solid #f1f5f9;">
                                <td style="padding: 1rem 1.5rem; font-weight: 600; color: #1f2937;">{{ $car->brand }} {{ $car->model }}</td>
                                <td style="padding: 1rem 1.5rem; color: #4b5563; font-family: monospace;">{{ $car->plate_number ?? '—' }}</td>
                                <td style="padding: 1rem 1.5rem; color: #4b5563;">{{ $car->type }}</td>
                                <td style="padding: 1rem 1.5rem; color: #4b5563;">{{ ucfirst($car->transmission) }}</td>
                                <td style="padding: 1rem 1.5rem; color: #4b5563;">RM {{ number_format($car->price_per_day, 2) }}</td>
                                <td style="padding: 1rem 1.5rem;">
                                    <span style="display: inline-block; padding: 0.3rem 0.85rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">
                                        {{ ucfirst($car->status ?? 'available') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 3rem; text-align: center; color: #9ca3af;">
                                    No cars assigned to your branch yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
