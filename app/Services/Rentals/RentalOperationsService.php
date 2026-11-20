<?php

namespace App\Services\Rentals;

use App\Enums\ChargeType;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Car;
use App\Models\Payment;
use App\Models\User;
use App\Models\VehicleInspection;
use App\Notifications\PickupCompleted;
use App\Notifications\ReturnCompleted;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Runs the physical side of a rental: handing a car over, taking it back,
 * and the extras that follow. Closes the booking lifecycle —
 *
 *   approved ──pick-up──▶ on trip ──return (all cars)──▶ completed
 *
 * and keeps cars.status in step (rented while out; available, or
 * maintenance when staff flag damage, once back).
 *
 * Photos and signatures are customer data: they go on the private
 * "local" disk and are only served through an authorised controller.
 */
class RentalOperationsService
{
    public const DISK = 'local';

    public function __construct(
        private ChargeCalculator $calculator,
        private PaymentService $payments,
    ) {}

    public function pickupBlocker(Booking $booking, Car $car): ?string
    {
        $booking->loadMissing(['cars', 'inspections']);

        return match (true) {
            ! $booking->cars->contains('id', $car->id) => 'This car is not part of the booking.',
            ! $booking->isApproved() => 'Only confirmed bookings can be handed over.',
            $booking->inspectionFor($car->id, VehicleInspection::PICKUP) !== null => 'This car has already been handed over.',
            today()->lt($booking->start_date) => 'Pick-up opens on '.$booking->start_date->format('D, d M').'.',
            today()->gt($booking->end_date) => 'The rental period has ended without a pick-up.',
            default => $booking->driverBlocker(),
        };
    }

    public function returnBlocker(Booking $booking, Car $car): ?string
    {
        $booking->loadMissing(['cars', 'inspections']);

        return match (true) {
            ! $booking->cars->contains('id', $car->id) => 'This car is not part of the booking.',
            $booking->inspectionFor($car->id, VehicleInspection::PICKUP) === null => 'This car has not been picked up yet.',
            $booking->inspectionFor($car->id, VehicleInspection::RETURN) !== null => 'This car has already been returned.',
            default => null,
        };
    }

    /** The rental must be settled before keys change hands. */
    public function requiresPaymentAtPickup(Booking $booking): bool
    {
        return (bool) config('payments.enabled') && ! $booking->isPaid();
    }

    /** Last odometer reading recorded for this car at any handover. */
    public function lastOdometer(Car $car): ?int
    {
        return VehicleInspection::where('car_id', $car->id)->latest('performed_at')->latest('id')->value('odometer_km');
    }

    /**
     * @throws ValidationException
     */
    public function pickUp(Booking $booking, Car $car, User $staff, HandoverData $data): VehicleInspection
    {
        $inspection = DB::transaction(function () use ($booking, $car, $staff, $data) {
            $booking = $this->lock($booking);

            if ($reason = $this->pickupBlocker($booking, $car)) {
                throw ValidationException::withMessages(['handover' => $reason]);
            }

            $this->assertOdometerNotBelow($data->odometer, $this->lastOdometer($car), 'the last recorded reading');

            if ($this->requiresPaymentAtPickup($booking)) {
                if (! $data->counterPayment) {
                    throw ValidationException::withMessages([
                        'payment' => 'This booking is unpaid. Take payment at the counter before handing over the keys.',
                    ]);
                }

                $this->payments->recordCounterPayment(
                    $booking,
                    Payment::PURPOSE_BOOKING,
                    $data->counterPayment['method'],
                    $data->counterPayment['reference'] ?? null,
                    $staff,
                );
            }

            $inspection = $this->createInspection($booking, $car, $staff, VehicleInspection::PICKUP, $data);

            $car->setStatus('rented');
            $booking->update(['picked_up_at' => $booking->picked_up_at ?? now()]);
            $this->forgetDashboardCache($booking);

            return $inspection;
        });

        $booking->user?->notify(new PickupCompleted($inspection));

        return $inspection;
    }

    /**
     * @param  array<int, array{type?: string, description?: string, amount?: float|int|string}>  $extraCharges  staff-added damage/cleaning/other
     *
     * @throws ValidationException
     */
    public function returnCar(Booking $booking, Car $car, User $staff, HandoverData $data, array $extraCharges = [], bool $needsMaintenance = false): VehicleInspection
    {
        [$inspection, $charges] = DB::transaction(function () use ($booking, $car, $staff, $data, $extraCharges, $needsMaintenance) {
            $booking = $this->lock($booking);

            if ($reason = $this->returnBlocker($booking, $car)) {
                throw ValidationException::withMessages(['handover' => $reason]);
            }

            $pickup = $booking->inspectionFor($car->id, VehicleInspection::PICKUP);
            $this->assertOdometerNotBelow($data->odometer, $pickup->odometer_km, 'the pick-up reading');

            $inspection = $this->createInspection($booking, $car, $staff, VehicleInspection::RETURN, $data, $needsMaintenance);

            $lines = $this->calculator->forReturn(
                $booking, $car, $pickup->odometer_km, $pickup->fuel_level, $inspection->performed_at, $data->odometer, $data->fuel,
            );

            foreach ($extraCharges as $extra) {
                $type = ChargeType::tryFrom((string) ($extra['type'] ?? '')) ?? ChargeType::Other;
                $amount = round((float) ($extra['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }
                $lines[] = [
                    'type' => $type,
                    'description' => "{$car->name}: ".Str::limit(trim((string) ($extra['description'] ?? $type->label())), 200, ''),
                    'quantity' => 1,
                    'unit_amount' => $amount,
                    'amount' => $amount,
                ];
            }

            $charges = collect($lines)->map(fn (array $line) => $booking->charges()->create($line + [
                'car_id' => $car->id,
                'created_by' => $staff->id,
            ]));

            $car->setStatus($needsMaintenance ? 'maintenance' : 'available');

            $returned = $booking->inspections()->where('type', VehicleInspection::RETURN)->pluck('car_id');
            if ($booking->cars->pluck('id')->diff($returned)->isEmpty()) {
                $booking->update([
                    'status' => 'completed',
                    'returned_at' => now(),
                    'completed_at' => now(),
                ]);
            }

            $this->forgetDashboardCache($booking);

            return [$inspection, $charges];
        });

        $booking->user?->notify(new ReturnCompleted($inspection, $charges));

        return $inspection;
    }

    /**
     * Live preview of the automatic charges for the handover form.
     *
     * @return array<int, array{type: ChargeType, description: string, quantity: float, unit_amount: float, amount: float}>
     */
    public function quoteReturn(Booking $booking, Car $car, int $odometer, int $fuel): array
    {
        $pickup = $booking->inspectionFor($car->id, VehicleInspection::PICKUP);

        if (! $pickup) {
            return [];
        }

        return $this->calculator->forReturn($booking, $car, $pickup->odometer_km, $pickup->fuel_level, now(), max($odometer, $pickup->odometer_km), $fuel);
    }

    public function addCharge(Booking $booking, ?Car $car, ChargeType $type, string $description, float $amount, User $staff): BookingCharge
    {
        $charge = $booking->charges()->create([
            'car_id' => $car?->id,
            'type' => $type,
            'description' => Str::limit(trim($description), 250, ''),
            'quantity' => 1,
            'unit_amount' => round($amount, 2),
            'amount' => round($amount, 2),
            'created_by' => $staff->id,
        ]);

        return $charge;
    }

    /**
     * @throws ValidationException
     */
    public function voidCharge(BookingCharge $charge, string $reason, User $staff): void
    {
        if ($charge->isPaid()) {
            throw ValidationException::withMessages(['charge' => 'A paid charge cannot be waived — refund the payment instead.']);
        }

        $charge->update([
            'voided_at' => now(),
            'void_reason' => Str::limit(trim($reason).' (by '.$staff->name.')', 300, ''),
        ]);
    }

    /**
     * The operations board: what each branch needs to hand over or take
     * back, one row per booking × car.
     *
     * @return array<string, Collection<int, array{booking: Booking, car: Car}>>
     */
    public function board(?int $branchId): array
    {
        $bookings = Booking::approved()
            ->with(['user', 'cars.branch', 'inspections'])
            ->when($branchId, fn ($q) => $q->whereHas('cars', fn ($c) => $c->where('branch_id', $branchId)))
            ->whereDate('start_date', '<=', today()->addDays(3))
            ->whereDate('end_date', '>=', today()->subDays(14))
            ->orderBy('start_date')
            ->get();

        $buckets = collect(['overdue', 'returns_today', 'pickups_today', 'on_trip', 'upcoming', 'no_show'])
            ->mapWithKeys(fn ($k) => [$k => collect()])
            ->all();

        foreach ($bookings as $booking) {
            foreach ($booking->cars as $car) {
                if ($branchId && $car->branch_id !== $branchId) {
                    continue;
                }

                $pickedUp = $booking->inspectionFor($car->id, VehicleInspection::PICKUP) !== null;
                $returned = $booking->inspectionFor($car->id, VehicleInspection::RETURN) !== null;
                $row = ['booking' => $booking, 'car' => $car];

                $bucket = match (true) {
                    $returned => null,
                    $pickedUp && $booking->isOverdue() => 'overdue',
                    $pickedUp && $booking->end_date->isSameDay(today()) => 'returns_today',
                    $pickedUp => 'on_trip',
                    $booking->end_date->lt(today()) => 'no_show',
                    $booking->start_date->lte(today()) => 'pickups_today',
                    default => 'upcoming',
                };

                if ($bucket) {
                    $buckets[$bucket]->push($row);
                }
            }
        }

        return $buckets;
    }

    /**
     * @return array<string, int>
     */
    public function summary(?int $branchId): array
    {
        return collect($this->board($branchId))->map->count()->all();
    }

    private function createInspection(Booking $booking, Car $car, User $staff, string $type, HandoverData $data, bool $needsMaintenance = false): VehicleInspection
    {
        $inspection = $booking->inspections()->create([
            'car_id' => $car->id,
            'type' => $type,
            'odometer_km' => $data->odometer,
            'fuel_level' => max(0, min(8, $data->fuel)),
            'checklist' => collect(config('rentals.checklist'))->keys()->mapWithKeys(fn ($k) => [$k => (bool) ($data->checklist[$k] ?? false)])->all(),
            'damages' => collect($data->damages)
                ->filter(fn ($kind, $zone) => isset(VehicleInspection::ZONES[$zone]) && isset(VehicleInspection::DAMAGE_KINDS[$kind]))
                ->all(),
            'notes' => $data->notes ? Str::limit(trim($data->notes), 2000, '') : null,
            'signed_by_name' => $data->signedByName ? Str::limit(trim($data->signedByName), 120, '') : null,
            'needs_maintenance' => $needsMaintenance,
            'performed_by' => $staff->id,
            'performed_at' => now(),
        ]);

        $dir = "inspections/{$booking->id}/{$inspection->id}";

        foreach (array_slice($data->photos, 0, (int) config('rentals.photos.max_count', 12)) as $photo) {
            $inspection->photos()->create(['path' => $photo->store($dir, self::DISK)]);
        }

        if ($data->signature) {
            $inspection->update(['signature_path' => $this->storeSignature($data->signature, $dir)]);
        }

        return $inspection;
    }

    /**
     * @throws ValidationException
     */
    private function storeSignature(string $dataUrl, string $dir): string
    {
        $prefix = 'data:image/png;base64,';
        $binary = str_starts_with($dataUrl, $prefix) ? base64_decode(substr($dataUrl, strlen($prefix)), true) : false;

        if ($binary === false || strlen($binary) > 512 * 1024 || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            throw ValidationException::withMessages(['signature' => 'The signature could not be read. Please sign again.']);
        }

        $path = "{$dir}/signature.png";
        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    /**
     * @throws ValidationException
     */
    private function assertOdometerNotBelow(int $odometer, ?int $floor, string $what): void
    {
        if ($floor !== null && $odometer < $floor) {
            throw ValidationException::withMessages([
                'odometer' => 'The odometer can’t be lower than '.$what.' ('.number_format($floor).' km).',
            ]);
        }
    }

    private function lock(Booking $booking): Booking
    {
        return Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail()->load(['cars', 'inspections', 'user.driverProfile']);
    }

    private function forgetDashboardCache(Booking $booking): void
    {
        DashboardService::forgetCacheFor(null);
        foreach ($booking->cars->pluck('branch_id')->unique() as $branchId) {
            DashboardService::forgetCacheFor($branchId);
        }
    }
}
