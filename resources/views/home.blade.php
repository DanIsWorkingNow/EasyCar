@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <!-- Welcome Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 30px; padding: 3rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); text-align: center; position: relative; overflow: hidden;">
            
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1; animation: float 6s ease-in-out infinite;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 60px; height: 60px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1; animation: float 4s ease-in-out infinite reverse;"></div>
            
            <!-- Success Alert -->
            @if (session('status'))
                <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 2rem; font-weight: 600; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">
                    ✅ {{ session('status') }}
                </div>
            @endif

            <!-- Main Heading -->
            <h1 style="font-size: 3rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 1rem 0; line-height: 1.2;">
                🎉 Welcome to EasyCar!
            </h1>
            
            <!-- User Greeting -->
            <p style="color: #6b7280; font-size: 1.3rem; margin: 0 0 1rem 0;">
                Hello, <span style="font-weight: 700; color: #1f2937;">{{ Auth::user()->name }}</span>!
            </p>
            
            <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.75rem 2rem; border-radius: 25px; display: inline-block; font-weight: 600; margin-bottom: 2rem; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);">
                🔑 You are successfully logged in!
            </div>
        </div>

        <!-- Quick Access Dashboard -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <h2 style="color: #1f2937; font-weight: 700; margin-bottom: 2rem; font-size: 1.8rem; text-align: center;">
                🚀 Quick Access Dashboard
            </h2>
            
            <div class="row g-4">
                <!-- Browse Cars -->
                <div class="col-md-6">
                    <a href="{{ url('/cars') }}" style="display: block; text-decoration: none; height: 100%;">
                        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2.5rem; border-radius: 20px; text-align: center; transition: all 0.3s ease; box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3); height: 100%;"
                             onmouseover="this.style.transform='translateY(-5px) scale(1.02)'; this.style.boxShadow='0 20px 45px rgba(102, 126, 234, 0.4)'"
                             onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.3)'">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Browse Available Cars</h3>
                            <p style="opacity: 0.9; margin: 0; font-size: 1rem;">Explore our premium fleet and find your perfect vehicle</p>
                        </div>
                    </a>
                </div>

                <!-- My Bookings -->
                <div class="col-md-6">
                    <a href="{{ url('/bookings') }}" style="display: block; text-decoration: none; height: 100%;">
                        <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 2.5rem; border-radius: 20px; text-align: center; transition: all 0.3s ease; box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3); height: 100%;"
                             onmouseover="this.style.transform='translateY(-5px) scale(1.02)'; this.style.boxShadow='0 20px 45px rgba(16, 185, 129, 0.4)'"
                             onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.3)'">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">My Bookings</h3>
                            <p style="opacity: 0.9; margin: 0; font-size: 1rem;">View and manage your car rental reservations</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

 <!-- Admin Bookings -->
<div class="col-md-6">
    <a href="{{ route('admin.bookings.index') }}" style="display: block; text-decoration: none; height: 100%;">
        <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 2.5rem; border-radius: 20px; text-align: center; transition: all 0.3s ease; box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3); height: 100%;"
             onmouseover="this.style.transform='translateY(-5px) scale(1.02)'; this.style.boxShadow='0 20px 45px rgba(59, 130, 246, 0.4)'"
             onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 15px 35px rgba(59, 130, 246, 0.3)'">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🛠️</div>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Manage Bookings</h3>
            <p style="opacity: 0.9; margin: 0; font-size: 1rem;">Approve, reject, and manage all user bookings</p>
        </div>
    </a>
</div>


        <!-- Role-Based Access -->
        @if(Auth::user()->userLevel === 5)
            <!-- Admin Access -->
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h3 style="color: #1f2937; font-weight: 700; font-size: 1.8rem; margin-bottom: 0.5rem;">
                        🧑‍💼 Admin Control Panel
                    </h3>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">You have administrator privileges</p>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="{{ url('/admin/dashboard') }}" style="display: block; background: linear-gradient(45deg, #8b5cf6, #7c3aed); color: white; padding: 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(139, 92, 246, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(139, 92, 246, 0.3)'">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                            <div style="font-weight: 600;">Admin Dashboard</div>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ url('/admin/cars') }}" style="display: block; background: linear-gradient(45deg, #f59e0b, #d97706); color: white; padding: 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(245, 158, 11, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.3)'">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚙</div>
                            <div style="font-weight: 600;">Manage All Cars</div>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ url('/admin/users') }}" style="display: block; background: linear-gradient(45deg, #ef4444, #dc2626); color: white; padding: 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(239, 68, 68, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(239, 68, 68, 0.3)'">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                            <div style="font-weight: 600;">Manage Users</div>
                        </a>
                    </div>
                </div>
            </div>
        @elseif(Auth::user()->userLevel === 1)
            <!-- Staff Access -->
            <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h3 style="color: #1f2937; font-weight: 700; font-size: 1.8rem; margin-bottom: 0.5rem;">
                        🧑‍🔧 Staff Control Panel
                    </h3>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">Branch: {{ Auth::user()->branch->name ?? 'Not Assigned' }}</p>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ url('/staff/dashboard') }}" style="display: block; background: linear-gradient(45deg, #06b6d4, #0891b2); color: white; padding: 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(6, 182, 212, 0.3);"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(6, 182, 212, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(6, 182, 212, 0.3)'">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                            <div style="font-weight: 600;">Staff Dashboard</div>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="{{ url('/staff/cars') }}" style="display: block; background: linear-gradient(45deg, #f59e0b, #d97706); color: white; padding: 1.5rem; border-radius: 15px; text-decoration: none; text-align: center; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);"
                           onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 35px rgba(245, 158, 11, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.3)'">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚙</div>
                            <div style="font-weight: 600;">Branch Cars</div>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- User Information -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <h4 style="color: #1f2937; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.5rem;">👤</span>
                Account Information
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px;">
                    <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Name</div>
                    <div style="color: #1f2937; font-weight: 600; font-size: 1.1rem;">{{ Auth::user()->name }}</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px;">
                    <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Email</div>
                    <div style="color: #1f2937; font-weight: 600; font-size: 1.1rem;">{{ Auth::user()->email }}</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px;">
                    <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Role</div>
                    <div style="color: #1f2937; font-weight: 600; font-size: 1.1rem;">
                        @if(Auth::user()->userLevel === 5)
                            🧑‍💼 Administrator
                        @elseif(Auth::user()->userLevel === 1)
                            🧑‍🔧 Staff Member
                        @else
                            👤 Customer
                        @endif
                    </div>
                </div>
                
                @if(Auth::user()->branch)
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 15px;">
                    <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">Branch</div>
                    <div style="color: #1f2937; font-weight: 600; font-size: 1.1rem;">📍 {{ Auth::user()->branch->name }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @media (max-width: 768px) {
        h1 {
            font-size: 2rem !important;
        }
        
        .container {
            padding: 0 1rem;
        }
    }
</style>
@endsection