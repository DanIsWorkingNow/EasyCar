@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1;"></div>
            <div style="position: absolute; bottom: -20px; left: -20px; width: 60px; height: 60px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1;"></div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="font-size: 3rem;">🧑‍💼</div>
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">
                        Admin Dashboard
                    </h1>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">
                        Live booking analytics — updates automatically every 15 seconds.
                    </p>
                </div>
            </div>
        </div>

        <!-- Live dashboard (KPI cards, trend chart, branch comparison, pending-approval queue) -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 1rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <livewire:dashboard.dashboard-index />
        </div>

        <!-- Quick Actions Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-top: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <h3 style="color: #1f2937; font-weight: 700; margin-bottom: 1.5rem; font-size: 1.5rem;">Quick Actions</h3>

            <div class="actions-grid">
                <a href="{{ route('cars.index') }}" class="action-btn" style="background: linear-gradient(45deg, #667eea, #764ba2);">🚗 Manage Cars</a>
                <a href="{{ route('admin.bookings.index') }}" class="action-btn" style="background: linear-gradient(45deg, #10b981, #059669);">📋 Manage Bookings</a>
                <a href="{{ route('admin.users.index') }}" class="action-btn" style="background: linear-gradient(45deg, #f59e0b, #d97706);">👥 Manage Users</a>
                <a href="{{ route('home') }}" class="action-btn" style="background: linear-gradient(45deg, #8b5cf6, #7c3aed);">🏠 Back to Home</a>
            </div>
        </div>
    </div>
</div>
@endsection
