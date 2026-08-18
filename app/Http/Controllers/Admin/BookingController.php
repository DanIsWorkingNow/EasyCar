<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings with filtering options
     */
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'cars', 'approvedBy']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Search by customer name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get counts for dashboard stats
        $stats = [
            'total' => Booking::count(),
            'pending' => Booking::pending()->count(),
            'approved' => Booking::approved()->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'stats'));
    }

    /**
     * Show the details of a specific booking
     */
    public function show(Booking $booking)
    {
        $booking->load(['user', 'cars.branch', 'approvedBy']);
        
        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Approve a booking
     */
    public function approve(Request $request, Booking $booking)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        if (!$booking->canBeApproved()) {
            return back()->with('error', 'This booking cannot be approved. It may already be processed or doesn\'t meet approval criteria.');
        }

        DB::beginTransaction();
        try {
            $success = $booking->approve(Auth::user(), $request->approval_notes);
            
            if ($success) {
                // You can add email notification here
                // $this->sendApprovalNotification($booking);
                
                DB::commit();
                return back()->with('success', 'Booking has been approved successfully! Customer will be notified.');
            } else {
                DB::rollback();
                return back()->with('error', 'Failed to approve booking. Please try again.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'An error occurred while approving the booking: ' . $e->getMessage());
        }
    }

    /**
     * Reject a booking
     */
    public function reject(Request $request, Booking $booking)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        if (!$booking->canBeRejected()) {
            return back()->with('error', 'This booking cannot be rejected. It may already be processed.');
        }

        DB::beginTransaction();
        try {
            $success = $booking->reject(Auth::user(), $request->rejection_reason);
            
            if ($success) {
                // You can add email notification here
                // $this->sendRejectionNotification($booking);
                
                DB::commit();
                return back()->with('success', 'Booking has been rejected. Customer will be notified.');
            } else {
                DB::rollback();
                return back()->with('error', 'Failed to reject booking. Please try again.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'An error occurred while rejecting the booking: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve multiple bookings
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id',
            'bulk_approval_notes' => 'nullable|string|max:500'
        ]);

        $bookingIds = $request->booking_ids;
        $notes = $request->bulk_approval_notes;
        $approvedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($bookingIds as $bookingId) {
                $booking = Booking::find($bookingId);
                
                if ($booking && $booking->canBeApproved()) {
                    if ($booking->approve(Auth::user(), $notes)) {
                        $approvedCount++;
                    }
                } else {
                    $errors[] = "Booking #{$bookingId} cannot be approved.";
                }
            }

            DB::commit();
            
            $message = "Successfully approved {$approvedCount} booking(s).";
            if (!empty($errors)) {
                $message .= " Some bookings could not be approved: " . implode(', ', $errors);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'An error occurred during bulk approval: ' . $e->getMessage());
        }
    }

    /**
     * Get booking statistics for dashboard
     */
    public function getStats()
    {
        return [
            'total_bookings' => Booking::count(),
            'pending_approvals' => Booking::pending()->count(),
            'approved_today' => Booking::approved()->whereDate('approved_at', today())->count(),
            'rejected_this_month' => Booking::where('status', 'rejected')
                                          ->whereMonth('created_at', now()->month)
                                          ->count(),
            'revenue_this_month' => Booking::approved()
                                          ->whereMonth('created_at', now()->month)
                                          ->sum('total_price')
        ];
    }


    public function export()
{
    $bookings = Booking::with('user', 'cars')->get();
    $filename = 'bookings_export_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($bookings) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['Booking ID', 'Customer Name', 'Start Date', 'End Date', 'Status', 'Car(s)']);

        foreach ($bookings as $booking) {
            $cars = $booking->cars->map(fn ($car) => "{$car->brand} {$car->model}")->implode('; ');
            fputcsv($handle, [
                $booking->id,
                $booking->user->name,
                $booking->start_date,
                $booking->end_date,
                $booking->status,
                $cars,
            ]);
        }

        fclose($handle);
    }, $filename, ['Content-Type' => 'text/csv']);
}

    /**
     * Private method to send approval notification (implement as needed)
     */
    private function sendApprovalNotification(Booking $booking)
    {
        // Implement email notification logic here
        // You can use Laravel's Mail facade or notification system
        
        /*
        Mail::to($booking->user->email)->send(new BookingApprovedMail($booking));
        */
    }

    /**
     * Private method to send rejection notification (implement as needed)
     */
    private function sendRejectionNotification(Booking $booking)
    {
        // Implement email notification logic here
        
        /*
        Mail::to($booking->user->email)->send(new BookingRejectedMail($booking));
        */
    }
}