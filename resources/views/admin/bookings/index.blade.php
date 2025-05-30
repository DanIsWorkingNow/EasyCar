@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <!-- Header Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; margin-bottom: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">
                        📋 Booking Management
                    </h1>
                    <p style="color: #6b7280; font-size: 1.1rem; margin: 0.5rem 0 0 0;">Approve, reject, and manage customer bookings</p>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="{{ route('bookings.export', request()->query()) }}" 
                       style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 28px rgba(16, 185, 129, 0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(16, 185, 129, 0.3)'">
                        📊 Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-top: 4px solid #667eea;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #667eea;">{{ $stats['total'] }}</div>
                    <div style="color: #6b7280; font-weight: 600;">Total Bookings</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-top: 4px solid #f59e0b;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;">{{ $stats['pending'] }}</div>
                    <div style="color: #6b7280; font-weight: 600;">Pending Approval</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-top: 4px solid #10b981;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #10b981;">{{ $stats['approved'] }}</div>
                    <div style="color: #6b7280; font-weight: 600;">Approved</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-top: 4px solid #ef4444;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">❌</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #ef4444;">{{ $stats['rejected'] }}</div>
                    <div style="color: #6b7280; font-weight: 600;">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <form method="GET" action="{{ route('bookings.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block;">Status</label>
                        <select name="status" class="form-select" style="border-radius: 10px; border: 2px solid #e5e7eb; background: white;">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✅ Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌ Rejected</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>🎉 Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>🚫 Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block;">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control" style="border-radius: 10px; border: 2px solid #e5e7eb;">
                    </div>
                    
                    <div class="col-md-2">
                        <label style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block;">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control" style="border-radius: 10px; border: 2px solid #e5e7eb;">
                    </div>
                    
                    <div class="col-md-3">
                        <label style="color: #374151; font-weight: 600; margin-bottom: 0.5rem; display: block;">Search Customer</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..." class="form-control" style="border-radius: 10px; border: 2px solid #e5e7eb;">
                    </div>
                    
                    <div class="col-md-3">
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; flex: 1;">
                                🔍 Filter
                            </button>
                            <a href="{{ route('bookings.index') }}" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1rem; border-radius: 10px; text-decoration: none; display: flex; align-items: center; transition: all 0.3s ease;">
                                🔄
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 2rem; font-weight: 600; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: linear-gradient(45deg, #ef4444, #dc2626); color: white; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 2rem; font-weight: 600; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);">
                ❌ {{ session('error') }}
            </div>
        @endif

        <!-- Bookings Table -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <!-- Bulk Actions -->
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                <form id="bulkActionForm" method="POST" action="{{ route('bookings.bulk-approve') }}">
                    @csrf
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="selectAll" style="transform: scale(1.2);">
                            <label for="selectAll" style="font-weight: 600; color: #374151;">Select All</label>
                        </div>
                        
                        <button type="submit" id="bulkApproveBtn" disabled 
                                style="background: linear-gradient(45deg, #10b981, #059669); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; opacity: 0.5; transition: all 0.3s ease;">
                            ✅ Bulk Approve
                        </button>
                        
                        <input type="text" name="bulk_approval_notes" placeholder="Optional approval notes..." class="form-control" style="flex: 1; border-radius: 8px; border: 2px solid #e5e7eb;">
                    </div>
                </form>
            </div>

            <!-- Table Content -->
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="background: white;">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Cars</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $index => $booking)
                            <tr>
                                <td>
                                    <input type="checkbox" class="bookingCheckbox" name="booking_ids[]" value="{{ $booking->id }}" form="bulkActionForm">
                                </td>
                                <td>{{ $bookings->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $booking->user->name }}</strong><br>
                                    <small class="text-muted">{{ $booking->user->email }}</small>
                                </td>
                                <td>
                                    @foreach($booking->cars as $car)
                                        <div class="mb-1">
                                            <span class="badge bg-light text-dark">{{ $car->brand }} {{ $car->model }}</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $statusConfig = [
                                            'pending' => ['class' => 'warning text-dark', 'icon' => '⏳'],
                                            'approved' => ['class' => 'success', 'icon' => '✅'],
                                            'rejected' => ['class' => 'danger', 'icon' => '❌'],
                                            'completed' => ['class' => 'info', 'icon' => '🎉'],
                                            'cancelled' => ['class' => 'secondary', 'icon' => '🚫']
                                        ];
                                        $status = $statusConfig[$booking->status] ?? ['class' => 'secondary', 'icon' => ''];
                                    @endphp
                                    <span class="badge bg-{{ $status['class'] }}">
                                        {{ $status['icon'] }} {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">
                                            👁️ View
                                        </a>
                                        
                                        @if($booking->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success" onclick="approveBooking({{ $booking->id }})">
                                                ✅ Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="rejectBooking({{ $booking->id }})">
                                                ❌ Reject
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    📭 No bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div style="padding: 1.5rem;">
                    {{ $bookings->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modals for Approve/Reject -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✅ Approve Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p>Are you sure you want to approve this booking?</p>
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3" placeholder="Add any notes for the approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">✅ Approve Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">❌ Reject Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to reject this booking?</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">❌ Reject Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.bookingCheckbox');
        const bulkBtn = document.getElementById('bulkApproveBtn');
        
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        
        updateBulkButton();
    });

    // Individual checkbox change
    document.querySelectorAll('.bookingCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkButton();
            
            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.bookingCheckbox');
            const checkedCheckboxes = document.querySelectorAll('.bookingCheckbox:checked');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            if (checkedCheckboxes.length === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedCheckboxes.length === allCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
        });
    });

    function updateBulkButton() {
        const checkedBoxes = document.querySelectorAll('.bookingCheckbox:checked');
        const bulkBtn = document.getElementById('bulkApproveBtn');
        
        if (checkedBoxes.length > 0) {
            bulkBtn.disabled = false;
            bulkBtn.style.opacity = '1';
        } else {
            bulkBtn.disabled = true;
            bulkBtn.style.opacity = '0.5';
        }
    }

    // Approve booking function
    function approveBooking(bookingId) {
        const form = document.getElementById('approveForm');
        form.action = `/admin/bookings/${bookingId}/approve`;
        
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }

    // Reject booking function
    function rejectBooking(bookingId) {
        const form = document.getElementById('rejectForm');
        form.action = `/admin/bookings/${bookingId}/reject`;
        
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }

    // Bulk action confirmation
    document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.bookingCheckbox:checked');
        
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one booking to approve.');
            return;
        }
        
        if (!confirm(`Are you sure you want to approve ${checkedBoxes.length} booking(s)?`)) {
            e.preventDefault();
        }
    });
</script>

@endsection