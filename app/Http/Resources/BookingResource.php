<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'payment_status' => $this->payment_status?->value,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'total_days' => $this->total_days,
            'total_price' => (float) $this->total_price,
            'approval_notes' => $this->approval_notes,
            'rejection_reason' => $this->rejection_reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'cars' => CarResource::collection($this->whenLoaded('cars')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
