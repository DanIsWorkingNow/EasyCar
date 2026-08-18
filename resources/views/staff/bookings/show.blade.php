@extends('layouts.app')

@section('content')
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container" style="max-width: 800px;">

        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div>
                    <h1 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0;">Booking #{{ $booking->id }}</h1>
                    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">{{ $booking->user->name }} · {{ $booking->user->email }}</p>
                </div>
                {!! $booking->status_badge !!}
            </div>

            @if(session('success'))
                <div style="background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 0.9rem 1.25rem; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 600;">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.9rem 1.25rem; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 600;">
                    ❌ {{ session('error') }}
                </div>
            @endif

            <dl class="grid grid-cols-2 gap-3" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <dt style="font-size: 0.8rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Start Date</dt>
                    <dd style="margin: 0;">{{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt style="font-size: 0.8rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">End Date</dt>
                    <dd style="margin: 0;">{{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt style="font-size: 0.8rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Total Days</dt>
                    <dd style="margin: 0;">{{ $booking->total_days }}</dd>
                </div>
                <div>
                    <dt style="font-size: 0.8rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Total Price</dt>
                    <dd style="margin: 0;">RM {{ number_format($booking->total_price, 2) }}</dd>
                </div>
            </dl>

            <h3 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">Cars</h3>
            <div style="margin-bottom: 1.5rem;">
                @foreach($booking->cars as $car)
                    <div style="padding: 0.75rem 1rem; background: #f9fafb; border-radius: 10px; margin-bottom: 0.5rem;">
                        <strong>{{ $car->brand }} {{ $car->model }}</strong>
                        <span style="color: #6b7280;"> · {{ $car->branch->name ?? 'N/A' }} · RM {{ number_format($car->pivot->price, 2) }}</span>
                    </div>
                @endforeach
            </div>

            @if($booking->status === 'rejected' && $booking->rejection_reason)
                <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <strong style="color: #b91c1c;">Rejection reason:</strong> {{ $booking->rejection_reason }}
                </div>
            @endif

            @if($booking->status === 'pending')
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-success" onclick="new bootstrap.Modal(document.getElementById('approveModal')).show()">
                        ✅ Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="new bootstrap.Modal(document.getElementById('rejectModal')).show()">
                        ❌ Reject
                    </button>
                </div>
            @endif

            <div style="margin-top: 1.5rem;">
                <a href="{{ route('staff.bookings.index') }}" style="color: #6b7280; text-decoration: none;">&larr; Back to branch bookings</a>
            </div>
        </div>
    </div>
</div>

<!-- Approve/Reject Modals -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✅ Approve Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('staff.bookings.approve', $booking) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3"></textarea>
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
            <form method="POST" action="{{ route('staff.bookings.reject', $booking) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required></textarea>
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
@endsection
