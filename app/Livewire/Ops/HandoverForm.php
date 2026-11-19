<?php

namespace App\Livewire\Ops;

use App\Enums\ChargeType;
use App\Models\Booking;
use App\Models\Car;
use App\Models\VehicleInspection;
use App\Payments\Gateways\CounterGateway;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The pick-up / return handover, done on a tablet at the branch counter:
 * readings, checklist, damage diagram, photos, payment (if still owed) or
 * extras (on return), and the customer's signature. Everything is
 * re-validated and authorised server-side by RentalOperationsService.
 */
class HandoverForm extends Component
{
    use AuthorizesRequests, WithFileUploads;

    #[Locked]
    public int $bookingId;

    #[Locked]
    public int $carId;

    #[Locked]
    public string $type;

    public $odometer = '';

    public int $fuel = 8;

    /** @var array<string, bool> */
    public array $checklist = [];

    /** @var array<string, string> zone => kind */
    public array $damages = [];

    public string $notes = '';

    public array $photos = [];

    public ?string $signature = null;

    public string $signedByName = '';

    public bool $licenceChecked = false;

    public string $paymentMethod = '';

    public string $paymentReference = '';

    public bool $needsMaintenance = false;

    /** @var array<int, array{type: string, description: string, amount: string}> */
    public array $extraCharges = [];

    public function mount(Booking $booking, Car $car, string $type, RentalOperationsService $ops): void
    {
        $this->authorize('handover', $car);
        abort_unless(in_array($type, [VehicleInspection::PICKUP, VehicleInspection::RETURN], true), 404);

        $this->bookingId = $booking->id;
        $this->carId = $car->id;
        $this->type = $type;
        $this->signedByName = (string) $booking->user?->name;

        $pickup = $booking->inspectionFor($car->id, VehicleInspection::PICKUP);

        if ($this->isReturn() && $pickup) {
            $this->odometer = (string) $pickup->odometer_km;
            $this->fuel = $pickup->fuel_level;
            $this->checklist = $pickup->checklist ?? [];
            $this->damages = $pickup->damages ?? [];
        } else {
            $this->odometer = (string) ($ops->lastOdometer($car) ?? '');
            $this->checklist = collect(config('rentals.checklist'))->map(fn () => true)->all();
        }
    }

    #[Computed]
    public function booking(): Booking
    {
        return Booking::with(['user', 'cars.branch', 'inspections', 'charges'])->findOrFail($this->bookingId);
    }

    #[Computed]
    public function car(): Car
    {
        return Car::with('branch')->findOrFail($this->carId);
    }

    #[Computed]
    public function pickup(): ?VehicleInspection
    {
        return $this->booking->inspectionFor($this->carId, VehicleInspection::PICKUP);
    }

    #[Computed]
    public function blocker(): ?string
    {
        $ops = app(RentalOperationsService::class);

        return $this->isReturn()
            ? $ops->returnBlocker($this->booking, $this->car)
            : $ops->pickupBlocker($this->booking, $this->car);
    }

    #[Computed]
    public function paymentRequired(): bool
    {
        return ! $this->isReturn() && app(RentalOperationsService::class)->requiresPaymentAtPickup($this->booking);
    }

    /** @return array<int, array{type: ChargeType, description: string, quantity: float, unit_amount: float, amount: float}> */
    #[Computed]
    public function quote(): array
    {
        if (! $this->isReturn() || ! is_numeric($this->odometer)) {
            return [];
        }

        return app(RentalOperationsService::class)->quoteReturn($this->booking, $this->car, (int) $this->odometer, $this->fuel);
    }

    public function isReturn(): bool
    {
        return $this->type === VehicleInspection::RETURN;
    }

    public function setFuel(int $eighths): void
    {
        $this->fuel = max(0, min(8, $eighths));
    }

    public function cycleDamage(string $zone): void
    {
        if (! isset(VehicleInspection::ZONES[$zone])) {
            return;
        }

        $kinds = array_keys(VehicleInspection::DAMAGE_KINDS);
        $current = $this->damages[$zone] ?? null;
        $next = $current === null ? $kinds[0] : ($kinds[array_search($current, $kinds, true) + 1] ?? null);

        if ($next === null) {
            unset($this->damages[$zone]);
        } else {
            $this->damages[$zone] = $next;
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function addExtraCharge(): void
    {
        $this->extraCharges[] = ['type' => ChargeType::Damage->value, 'description' => '', 'amount' => ''];
    }

    public function removeExtraCharge(int $index): void
    {
        unset($this->extraCharges[$index]);
        $this->extraCharges = array_values($this->extraCharges);
    }

    protected function rules(): array
    {
        return [
            'odometer' => 'required|integer|min:0|max:2000000',
            'fuel' => 'required|integer|min:0|max:8',
            'notes' => 'nullable|string|max:2000',
            'photos' => 'array|max:'.config('rentals.photos.max_count', 12),
            'photos.*' => 'image|max:'.config('rentals.photos.max_kb', 8192),
            'signature' => 'required|string|starts_with:data:image/png;base64,',
            'signedByName' => 'required|string|max:120',
            'licenceChecked' => $this->isReturn() ? 'boolean' : 'accepted',
            'paymentMethod' => [Rule::requiredIf(fn () => $this->paymentRequired), 'nullable', Rule::in(array_keys(CounterGateway::METHODS))],
            'paymentReference' => 'nullable|string|max:100',
            'extraCharges' => 'array|max:10',
            'extraCharges.*.type' => ['required', Rule::in(array_keys(ChargeType::manualOptions()))],
            'extraCharges.*.description' => 'required|string|max:200',
            'extraCharges.*.amount' => 'required|numeric|min:0.01|max:50000',
        ];
    }

    protected function messages(): array
    {
        return [
            'signature.required' => 'The customer needs to sign before you can finish.',
            'signature.starts_with' => 'The signature could not be read. Please sign again.',
            'licenceChecked.accepted' => 'Confirm you have checked the customer’s driving licence and IC/passport.',
            'paymentMethod.required' => 'Choose how the customer paid at the counter.',
            'photos.*.image' => 'Photos must be images.',
            'photos.*.max' => 'Each photo must be under '.round(config('rentals.photos.max_kb', 8192) / 1024).' MB.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'extraCharges.*.description' => 'charge description',
            'extraCharges.*.amount' => 'charge amount',
            'signedByName' => 'customer name',
        ];
    }

    public function submit(RentalOperationsService $ops)
    {
        $this->authorize('handover', $this->car);
        $this->validate();

        $data = new HandoverData(
            odometer: (int) $this->odometer,
            fuel: $this->fuel,
            checklist: $this->checklist,
            damages: $this->damages,
            notes: $this->notes ?: null,
            photos: $this->photos,
            signature: $this->signature,
            signedByName: $this->signedByName,
            counterPayment: $this->paymentRequired ? ['method' => $this->paymentMethod, 'reference' => $this->paymentReference ?: null] : null,
        );

        $inspection = $this->isReturn()
            ? $ops->returnCar($this->booking, $this->car, Auth::user(), $data, $this->extraCharges, $this->needsMaintenance)
            : $ops->pickUp($this->booking, $this->car, Auth::user(), $data);

        session()->flash('success', $this->isReturn()
            ? "{$this->car->name} checked in."
            : "{$this->car->name} handed over to {$this->booking->user->name}.");

        return redirect()->route('inspections.show', $inspection);
    }

    public function render()
    {
        return view('livewire.ops.handover-form', [
            'zones' => VehicleInspection::ZONES,
            'kinds' => VehicleInspection::DAMAGE_KINDS,
            'methods' => CounterGateway::METHODS,
            'chargeTypes' => ChargeType::manualOptions(),
        ]);
    }
}
