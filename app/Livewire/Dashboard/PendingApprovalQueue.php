<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Services\DashboardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * FR-DSH-05 (live queue) + FR-DSH-10 (bulk-approve from the widget).
 * Authorization reuses BookingPolicy directly ($this->authorize()) — the
 * exact same rule Admin\BookingController and Staff\BookingController
 * enforce, so there is exactly one place each approval rule is defined.
 *
 * NOTE: Livewire\Component does not include Laravel's AuthorizesRequests
 * trait by default (unlike a Controller) — it's added explicitly below so
 * $this->authorize() works the same way it does in the web controllers.
 */
class PendingApprovalQueue extends Component
{
    use AuthorizesRequests;

    public ?int $branchId = null;
    public array $selected = [];
    public string $rejectReasonFor = '';
    public string $rejectReason = '';

    public function approve(int $bookingId): void
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorize('approve', $booking);

        if ($booking->approve(Auth::user())) {
            DashboardService::forgetCacheFor($this->branchId);
            session()->flash('dashboard_success', "Booking #{$bookingId} approved.");
        } else {
            session()->flash('dashboard_error', "Booking #{$bookingId} could not be approved — it may no longer be available for those dates.");
        }
    }

    public function openReject(int $bookingId): void
    {
        $this->rejectReasonFor = (string) $bookingId;
        $this->rejectReason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejectReason' => 'required|string|max:500']);

        $booking = Booking::findOrFail((int) $this->rejectReasonFor);
        $this->authorize('reject', $booking);

        $booking->reject(Auth::user(), $this->rejectReason);
        DashboardService::forgetCacheFor($this->branchId);

        $this->rejectReasonFor = '';
        $this->rejectReason = '';
        session()->flash('dashboard_success', "Booking #{$booking->id} rejected.");
    }

    public function bulkApprove(): void
    {
        $approved = 0;

        foreach ($this->selected as $bookingId) {
            $booking = Booking::find($bookingId);

            if ($booking && Gate::allows('approve', $booking) && $booking->approve(Auth::user())) {
                $approved++;
            }
        }

        DashboardService::forgetCacheFor($this->branchId);
        $this->selected = [];
        session()->flash('dashboard_success', "{$approved} booking(s) approved.");
    }

    public function render()
    {
        $queue = app(DashboardService::class)->getPendingQueue($this->branchId);

        return view('livewire.dashboard.pending-approval-queue', ['queue' => $queue]);
    }
}
