@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1;"></div>
            <div style="position: absolute; bottom: -20px; left: -20px; width: 60px; height: 60px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1;"></div>
            
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="font-size: 3rem;">🧑‍🔧</div>
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">
                        Staff Dashboard
                    </h1>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">
                        Branch: <span style="font-weight: 600; color: #1f2937;">{{ Auth::user()->branch->name }}</span>
                    </p>
                </div>
            </div>
            
            <!-- Branch Info Badge -->
            <div style="display: inline-block; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.5rem 1.5rem; border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-top: 1rem;">
                📍 Managing {{ Auth::user()->branch->name }} Branch
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4">
            <div class="col-md-6">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2.5rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.2);"
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)'">
                    
                    <!-- Background decoration -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                    
                    <div style="font-size: 3.5rem; margin-bottom: 1rem;">🚗</div>
                    <div style="font-size: 3rem; font-weight: 800; color: #667eea; margin-bottom: 0.5rem;">
                        {{ $totalCars }}
                    </div>
                    <div style="color: #6b7280; font-weight: 600; font-size: 1.2rem;">Your Branch Cars</div>
                    <div style="color: #9ca3af; font-size: 1rem; margin-top: 0.5rem;">Available in {{ Auth::user()->branch->name }}</div>
                </div>
            </div>

            <div class="col-md-6">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2.5rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.2);"
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 25px 50px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)'">
                    
                    <!-- Background decoration -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #10b981, #059669);"></div>
                    
                    <div style="font-size: 3.5rem; margin-bottom: 1rem;">📋</div>
                    <div style="font-size: 3rem; font-weight: 800; color: #10b981; margin-bottom: 0.5rem;">
                        {{ $totalBookings }}
                    </div>
                    <div style="color: #6b7280; font-weight: 600; font-size: 1.2rem;">Your Branch Bookings</div>
                    <div style="color: #9ca3af; font-size: 1rem; margin-top: 0.5rem;">Reservations for your branch</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-top: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <h3 style="color: #1f2937; font-weight: 700; margin-bottom: 1.5rem; font-size: 1.5rem;">Staff Actions</h3>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="{{ route('cars.index') }}" style="display: block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 2rem 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚗</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">Manage Branch Cars</div>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 0.5rem;">Add, edit, remove vehicles</div>
                    </a>
                </div>
                
                <div class="col-md-4">
                    <a href="{{ route('bookings.index') }}" style="display: block; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 2rem 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.3)'">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📋</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">Manage Bookings</div>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 0.5rem;">Process reservations</div>
                    </a>
                </div>
                
                <div class="col-md-4">
                    <a href="{{ route('home') }}" style="display: block; background: linear-gradient(45deg, #8b5cf6, #7c3aed); color: white; padding: 2rem 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);"
                       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(139, 92, 246, 0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(139, 92, 246, 0.3)'">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏠</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">Back to Home</div>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 0.5rem;">Return to main site</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Branch Information -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2rem; margin-top: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <h4 style="color: #1f2937; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.5rem;">📍</span>
                Branch Information
            </h4>
            <div style="color: #6b7280; line-height: 1.6;">
                <p style="margin: 0.5rem 0;"><strong>Branch Name:</strong> {{ Auth::user()->branch->name }}</p>
                <p style="margin: 0.5rem 0;"><strong>Your Role:</strong> Branch Staff</p>
                <p style="margin: 0.5rem 0;"><strong>Access Level:</strong> Branch-specific management</p>
            </div>
        </div>
    </div>
</div>
@endsection