<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * FIX (TD-20): the original class only had index()/show(). The FSD describes
 * booking approval as something staff can already do, but in the running
 * application only Admin\BookingController had approve()/reject() — staff
 * had no way to approve or reject a booking at all. This mirrors
 * Admin\BookingController's approve()/reject()/bulkApprove() (including the
 * TD-04 re-validation, which lives in Booking::approve() itself and
 * therefore applies here automatically), with one addition: every action is
 * scoped to bookings whose cars belong to the staff member's own branch,
 * returning a 403 otherwise.
 */
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $branchId = Auth::user()->branch_id;

        $query = Booking::with(['user', 'cars', 'approvedBy'])
            ->whereHas('cars', fn ($q) => $q->where('branch_id', $branchId));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderByDesc('created_at')->paginate(15);

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        return view('staff.bookings.index', compact('bookings', 'stats'));
    }

    public function show(Booking $booking)
    {
        $this->authorizeBranch($booking);

        $booking->load(['user', 'cars.branch', 'approvedBy']);

        return view('staff.bookings.show', compact('booking'));
    }

    public function approve(Request $request, Booking $booking)
    {
        $this->authorizeBranch($booking);

        $request->validate(['approval_notes' => 'nullable|string|max:500']);

        if (! $booking->canBeApproved()) {
            return back()->with('error', 'This booking cannot be approved. It may already be processed or does not meet the approval criteria.');
        }

        DB::beginTransaction();
        try {
            $success = $booking->approve(Auth::user(), $request->approval_notes);
            DB::commit();

            return $success
                ? back()->with('success', 'Booking approved successfully.')
                : back()->with('error', 'Could not approve this booking — the car may no longer be available for these dates.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred while approving the booking.');
        }
    }

    public function reject(Request $request, Booking $booking)
    {
        $this->authorizeBranch($booking);

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        if (! $booking->canBeRejected()) {
            return back()->with('error', 'This booking cannot be rejected. It may already be processed.');
        }

        DB::beginTransaction();
        try {
            $success = $booking->reject(Auth::user(), $request->rejection_reason);
            DB::commit();

            return $success
                ? back()->with('success', 'Booking rejected.')
                : back()->with('error', 'Failed to reject booking. Please try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred while rejecting the booking.');
        }
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id',
            'bulk_approval_notes' => 'nullable|string|max:500',
        ]);

        $branchId = Auth::user()->branch_id;
        $approvedCount = 0;
        $skipped = [];

        DB::beginTransaction();
        try {
            foreach ($request->booking_ids as $bookingId) {
                $booking = Booking::with('cars')->find($bookingId);

                $inBranch = $booking && $booking->cars->every(fn ($car) => $car->branch_id === $branchId);

                if ($inBranch && $booking->canBeApproved() && $booking->approve(Auth::user(), $request->bulk_approval_notes)) {
                    $approvedCount++;
                } else {
                    $skipped[] = $bookingId;
                }
            }

            DB::commit();

            $message = "{$approvedCount} booking(s) approved.";
            if ($skipped) {
                $message .= ' Skipped (outside your branch, already processed, or no longer available): ' . implode(', ', $skipped);
            }

            return back()->with($skipped ? 'warning' : 'success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred during bulk approval.');
        }
    }

    /**
     * Abort with 403 unless every car on this booking belongs to the
     * authenticated staff member's branch.
     */
    private function authorizeBranch(Booking $booking): void
    {
        $branchId = Auth::user()->branch_id;
        $booking->loadMissing('cars');

        abort_unless(
            $booking->cars->isNotEmpty() && $booking->cars->every(fn ($car) => $car->branch_id === $branchId),
            403,
            'This booking belongs to another branch.'
        );
    }
}
