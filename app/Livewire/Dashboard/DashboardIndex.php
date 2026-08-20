<?php

namespace App\Livewire\Dashboard;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Root component. Holds the branch filter (Admin only — Staff never get
 * one, per FR-DSH-08) and the period filter, and passes both down to every
 * child widget as props so they all stay in sync.
 *
 * Mount enforces FR-DSH-08/09 in one place: branchId is fixed server-side
 * for Staff and can never be changed from the client, regardless of what a
 * tampered request might try to send.
 */
class DashboardIndex extends Component
{
    public ?int $branchId = null;

    public bool $isAdmin = false;

    public int $period = 30; // days

    public function mount(): void
    {
        abort_unless(Auth::user()->can('view-dashboard'), 403);

        $this->isAdmin = Auth::user()->hasRole('admin');
        $this->branchId = $this->isAdmin ? null : Auth::user()->branch_id;
    }

    public function setBranch(?int $branchId): void
    {
        abort_unless($this->isAdmin, 403); // only Admin may change scope at all

        $this->branchId = $branchId;
    }

    public function setPeriod(int $days): void
    {
        $this->period = in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    #[Computed]
    public function branches()
    {
        return $this->isAdmin ? Branch::orderBy('name')->get() : collect();
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-index');
    }
}
