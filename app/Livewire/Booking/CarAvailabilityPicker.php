<?php

namespace App\Livewire\Booking;

use App\Models\Addon;
use App\Models\Branch;
use App\Models\Car;
use App\Notifications\BookingConfirmed;
use App\Services\BookingAvailabilityService;
use App\Services\BookingCreationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The booking flow: dates → live availability → select up to two cars →
 * reserve. Live filtering is a convenience layer; createBooking() still
 * goes through BookingCreationService, which re-checks availability and
 * the per-customer limit server-side inside a transaction (FR-BKG-09).
 */
class CarAvailabilityPicker extends Component
{
    #[Url(as: 'from', except: null)]
    public ?string $startDate = null;

    #[Url(as: 'to', except: null)]
    public ?string $endDate = null;

    public ?int $branchId = null;

    public ?string $type = null;

    public ?string $transmission = null;

    public string $search = '';

    public array $selectedCarIds = [];

    public bool $acceptTerms = false;

    /** @var array<int|string, int> addon id => quantity */
    public array $addons = [];

    /** Typed into the input; becomes $appliedPromo once it validates. */
    public string $promoInput = '';

    public ?string $appliedPromo = null;

    public function mount(?int $preselectedCarId = null): void
    {
        if ($preselectedCarId && Car::whereKey($preselectedCarId)->exists()) {
            $this->selectedCarIds = [$preselectedCarId];
        }
    }

    protected function rules(): array
    {
        return [
            'startDate' => 'required|date|after_or_equal:'.now()->addDays(2)->toDateString(),
            'endDate' => 'required|date|after:startDate',
            'selectedCarIds' => 'required|array|min:1|max:'.BookingCreationService::MAX_CARS_PER_PERIOD,
            'selectedCarIds.*' => 'integer|exists:cars,id',
            'acceptTerms' => 'accepted',
            'addons' => 'array',
            'addons.*' => 'integer|min:0|max:10',
        ];
    }

    protected function messages(): array
    {
        return [
            'startDate.required' => 'Choose a pick-up date.',
            'endDate.required' => 'Choose a return date.',
            'startDate.after_or_equal' => 'Bookings must start at least 2 days from today.',
            'endDate.after' => 'Return must be after pick-up.',
            'selectedCarIds.required' => 'Select at least one car.',
            'selectedCarIds.max' => 'You can select at most 2 cars.',
            'acceptTerms.accepted' => 'Please accept the Terms of Rental to continue.',
        ];
    }

    public function updatedStartDate(): void
    {
        if ($this->endDate && $this->startDate && $this->endDate <= $this->startDate) {
            $this->endDate = null;
        }
        $this->resetErrorBag(['startDate', 'endDate']);
    }

    public function toggleCar(int $carId): void
    {
        if (in_array($carId, $this->selectedCarIds, true)) {
            $this->selectedCarIds = array_values(array_diff($this->selectedCarIds, [$carId]));
            $this->resetErrorBag('selectedCarIds');

            return;
        }

        if (count($this->selectedCarIds) >= BookingCreationService::MAX_CARS_PER_PERIOD) {
            $this->addError('selectedCarIds', 'You can select at most 2 cars.');

            return;
        }

        $this->resetErrorBag('selectedCarIds');
        $this->selectedCarIds[] = $carId;
    }

    public function setAddon(int $addonId, int $quantity): void
    {
        $addon = Addon::active()->find($addonId);
        if (! $addon) {
            return;
        }

        $quantity = max(0, min($quantity, $addon->max_quantity));
        if ($quantity === 0) {
            unset($this->addons[$addonId]);
        } else {
            $this->addons[$addonId] = $quantity;
        }
    }

    public function applyPromo(BookingCreationService $pricing): void
    {
        $this->resetErrorBag('promo_code');
        $code = strtoupper(trim($this->promoInput));

        if ($code === '') {
            $this->addError('promo_code', 'Enter a promo code.');

            return;
        }
        if (! $this->startDate || ! $this->endDate || ! $this->selectedCarIds) {
            $this->addError('promo_code', 'Choose your dates and a car first.');

            return;
        }

        $quote = $pricing->quote($this->selectedCarIds, $this->startDate, $this->endDate, $this->addons, Auth::user(), $code);

        if ($quote['promo_error']) {
            $this->addError('promo_code', $quote['promo_error']);

            return;
        }

        $this->appliedPromo = $code;
        $this->promoInput = '';
    }

    public function removePromo(): void
    {
        $this->appliedPromo = null;
        $this->resetErrorBag('promo_code');
    }

    public function clearFilters(): void
    {
        $this->reset(['branchId', 'type', 'transmission', 'search']);
    }

    public function createBooking(BookingCreationService $service)
    {
        $this->validate();

        try {
            $booking = $service->create(
                Auth::user(), $this->selectedCarIds, $this->startDate, $this->endDate, (string) request()->ip(),
                $this->addons, $this->appliedPromo,
            );
        } catch (ValidationException $e) {
            // A car was taken between page load and submit, the customer
            // already holds two cars for these dates, or the promo code
            // stopped being valid (e.g. its last redemption was taken).
            $errors = $e->errors();
            if (isset($errors['promo_code'])) {
                $this->appliedPromo = null;
                $this->addError('promo_code', $errors['promo_code'][0].' It has been removed — review your total and try again.');
            } else {
                $this->addError('selectedCarIds', collect($errors)->flatten()->first());
            }

            return;
        }

        Auth::user()->notify(new BookingConfirmed($booking));

        session()->flash('success', config('payments.enabled')
            ? "Booking {$booking->reference} reserved — complete payment to secure your car."
            : "Booking {$booking->reference} received and pending approval.");

        return redirect()->route('bookings.show', $booking);
    }

    public function render(BookingAvailabilityService $availability, BookingCreationService $pricing)
    {
        $hasDates = $this->startDate && $this->endDate && $this->endDate > $this->startDate;

        $unavailableIds = $hasDates ? $availability->unavailableCarIds($this->startDate, $this->endDate) : [];

        $cars = Car::with('branch')
            ->where('status', '!=', 'maintenance')
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->transmission, fn ($q) => $q->whereRaw('LOWER(transmission) = ?', [strtolower($this->transmission)]))
            ->search($this->search)
            ->orderBy('price_per_day')
            ->get();

        // Drop selections that became unavailable after a date change.
        if ($hasDates && array_intersect($this->selectedCarIds, $unavailableIds)) {
            $this->selectedCarIds = array_values(array_diff($this->selectedCarIds, $unavailableIds));
            $this->addError('selectedCarIds', 'A car you selected is not available for these dates and was removed.');
        }

        $view = view('livewire.booking.car-availability-picker', [
            'cars' => $cars,
            'unavailableIds' => $unavailableIds,
            'availableCount' => $cars->whereNotIn('id', $unavailableIds)->count(),
            'branches' => Branch::orderBy('name')->get(),
            'selectedCars' => Car::with('branch')->whereIn('id', $this->selectedCarIds)->get(),
            'quote' => $quote = $hasDates && $this->selectedCarIds
                ? $pricing->quote($this->selectedCarIds, $this->startDate, $this->endDate, $this->addons, Auth::user(), $this->appliedPromo)
                : null,
            'availableAddons' => Addon::active()->get(),
            'days' => $hasDates ? $pricing->totalDays($this->startDate, $this->endDate) : null,
            'hasDates' => $hasDates,
        ]);

        // A basket change can invalidate an applied code (e.g. min spend).
        if ($quote && $quote['promo_error'] && $this->appliedPromo) {
            $this->addError('promo_code', $quote['promo_error']);
        }

        return $view;
    }
}
