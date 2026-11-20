<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'payment_status' => $this->payment_status->value,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'total_days' => $this->total_days,
            'total_price' => (float) $this->total_price,
            'addons_amount' => (float) $this->addons_amount,
            'discount_amount' => (float) $this->discount_amount,
            'promo_code' => $this->whenLoaded('promoCode', fn () => $this->promoCode?->code),
            'addons' => $this->whenLoaded('addons', fn () => $this->addons->map(fn ($a) => [
                'id' => $a->addon_id,
                'name' => $a->name,
                'quantity' => $a->quantity,
                'amount' => (float) $a->amount,
            ])->values()),
            'trip_status' => $this->tripMeta()['key'] ?? null,
            'pickup_due_at' => $this->pickupDueAt()->toIso8601String(),
            'return_due_at' => $this->returnDueAt()->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'outstanding_charges' => $this->whenLoaded('charges', fn () => $this->outstandingCharges()),
            'approval_notes' => $this->approval_notes,
            'rejection_reason' => $this->rejection_reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'cars' => CarResource::collection($this->whenLoaded('cars')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
