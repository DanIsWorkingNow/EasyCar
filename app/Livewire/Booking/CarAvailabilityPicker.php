<?php

namespace App\Livewire\Booking;

use App\Models\Branch;
use App\Models\Car;
use App\Notifications\BookingConfirmed;
use App\Services\BookingAvailabilityService;
use App\Services\BookingCreationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * TSD Section 5.7.1. Owns the entire booking-creation interaction: date
 * range, filters, live availability, selection, and submission — a single
 * component rather than a picker feeding a separate HTML form, since
 * Livewire handles that more cleanly than syncing state between the two.
 *
 * FR-BKG-09: this component's live filtering is a convenience layer.
 * createBooking() below still goes through BookingCreationService, which
 * re-checks availability server-side before writing anything — the same
 * authoritative check BookingController::store() has always had.
 */
class CarAvailabilityPicker extends Component
{
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $branchId = null;
    public ?string $type = null;
    public ?string $transmission = null;
    public string $search = '';
    public array $selectedCarIds = [];

    protected function rules(): array
    {
        return [
            'startDate' => 'required|date|after_or_equal:' . now()->addDays(2)->toDateString(),
            'endDate' => 'required|date|after:startDate',
            'selectedCarIds' => 'required|array|min:1|max:2',
        ];
    }

    protected function messages(): array
    {
        return [
            'startDate.after_or_equal' => 'Bookings must start at least 2 days from today.',
            'selectedCarIds.required' => 'Select at least one car.',
            'selectedCarIds.max' => 'You can select at most 2 cars.',
        ];
    }

    public function toggleCar(int $carId): void
    {
        if (in_array($carId, $this->selectedCarIds, true)) {
            $this->selectedCarIds = array_values(array_diff($this->selectedCarIds, [$carId]));

            return;
        }

        if (count($this->selectedCarIds) >= 2) {
            $this->addError('selectedCarIds', 'You can select at most 2 cars.');

            return;
        }

        $this->resetErrorBag('selectedCarIds');
        $this->selectedCarIds[] = $carId;
    }

    public function clearFilters(): void
    {
        $this->reset(['branchId', 'type', 'transmission', 'search']);
    }

    public function createBooking(BookingCreationService $service)
    {
        $this->validate();

        try {
            $booking = $service->create(Auth::user(), $this->selectedCarIds, $this->startDate, $this->endDate);
        } catch (ValidationException $e) {
            // A car went from available to unavailable between page load and
            // submit (FR-BKG-09's race condition) — surface it the same way
            // any other validation error shows, then let the live grid
            // re-render on the next interaction with the now-current state.
            $this->addError('selectedCarIds', collect($e->errors())->flatten()->first());

            return;
        }

        if (class_exists(BookingConfirmed::class)) {
            // Requires Level 3. Safe no-op if applied out of order.
            Auth::user()->notify(new BookingConfirmed($booking->load('cars')));
        }

        session()->flash('success', 'Booking created successfully! Your booking is pending approval.');

        return redirect()->route('bookings.index');
    }

    public function render(BookingAvailabilityService $availability)
    {
        $unavailableIds = ($this->startDate && $this->endDate)
            ? $availability->unavailableCarIds($this->startDate, $this->endDate)
            : [];

        $cars = Car::with('branch')
            ->when($this->branchId, function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->when($this->type, function ($q) {
                $q->where('type', $this->type);
            })
            ->when($this->transmission, function ($q) {
                $q->where('transmission', $this->transmission);
            })
            ->when($this->search, function ($q) {
                // Portable case-insensitive match (TSD DB-03), same pattern
                // used throughout the API kit and Level 1 Part 2.
                $term = strtolower($this->search);
                $q->where(function ($qq) use ($term) {
                    $qq->whereRaw('LOWER(brand) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(model) LIKE ?', ["%{$term}%"]);
                });
            })
            ->get();

        return view('livewire.booking.car-availability-picker', [
            'cars' => $cars,
            'unavailableIds' => $unavailableIds,
            'availableCount' => $cars->whereNotIn('id', $unavailableIds)->count(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
