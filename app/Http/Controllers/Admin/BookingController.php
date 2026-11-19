<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = $this->filtered($request)
            ->with(['user', 'cars', 'approvedBy'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Booking::count(),
            'pending' => Booking::pending()->count(),
            'approved' => Booking::approved()->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'unpaid' => Booking::whereIn('status', ['pending', 'approved'])->where('payment_status', 'unpaid')->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'stats'));
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'cars.branch', 'approvedBy', 'payments' => fn ($q) => $q->latest(), 'inspections', 'charges']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function approve(Request $request, Booking $booking)
    {
        $request->validate(['approval_notes' => 'nullable|string|max:500']);

        if (! $booking->canBeApproved()) {
            return back()->with('error', 'This booking cannot be approved — it may already be processed or start too soon.');
        }

        $approved = DB::transaction(fn () => $booking->approve($request->user(), $request->approval_notes));

        return $approved
            ? back()->with('success', "Booking {$booking->reference} approved. The customer has been notified.")
            : back()->with('error', 'Could not approve — the car is already committed to another approved booking for these dates.');
    }

    public function reject(Request $request, Booking $booking)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        if (! $booking->canBeRejected()) {
            return back()->with('error', 'This booking cannot be rejected — it may already be processed.');
        }

        $booking->reject($request->user(), $request->rejection_reason);

        return back()->with('success', "Booking {$booking->reference} rejected. The customer has been notified".($booking->isPaid() ? '' : ' and any payment refunded').'.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'integer|exists:bookings,id',
            'bulk_approval_notes' => 'nullable|string|max:500',
        ]);

        $approved = 0;
        $skipped = [];

        foreach (Booking::with('cars')->whereIn('id', $request->booking_ids)->get() as $booking) {
            try {
                $ok = $booking->canBeApproved()
                    && DB::transaction(fn () => $booking->approve($request->user(), $request->bulk_approval_notes));
            } catch (\Throwable $e) {
                Log::error('Bulk approve failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
                $ok = false;
            }

            $ok ? $approved++ : $skipped[] = $booking->reference;
        }

        $message = "{$approved} booking(s) approved.";
        if ($skipped) {
            $message .= ' Skipped: '.implode(', ', $skipped).' (already processed, starting too soon, or unavailable).';
        }

        return back()->with($skipped ? 'warning' : 'success', $message);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with(['user', 'cars.branch'])->orderBy('id')->lazy(500)
            ->map(fn (Booking $b) => [
                $b->reference,
                $b->user?->name,
                $b->user?->email,
                $b->cars->map(fn ($c) => "{$c->brand} {$c->model}".($c->plate_number ? " ({$c->plate_number})" : ''))->implode('; '),
                $b->cars->pluck('branch.name')->filter()->unique()->implode('; '),
                $b->start_date->toDateString(),
                $b->end_date->toDateString(),
                $b->total_days,
                number_format((float) $b->total_price, 2, '.', ''),
                $b->status,
                $b->payment_status->value,
                $b->created_at?->toDateTimeString(),
            ]);

        return Csv::download('bookings_export_'.now()->format('Ymd_His').'.csv', [
            'Reference', 'Customer', 'Email', 'Car(s)', 'Branch', 'Start', 'End', 'Days',
            'Total (MYR)', 'Status', 'Payment', 'Created',
        ], $rows);
    }

    /** @return Builder<Booking> */
    private function filtered(Request $request): Builder
    {
        return Booking::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('start_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('end_date', '<=', $request->end_date))
            ->search($request->input('search'));
    }
}
