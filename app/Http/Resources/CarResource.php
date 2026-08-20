<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * SUPERSEDES the CarResource shipped in the API kit — adds plate_number
 * (FR-CAR-06).
 */
class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'type' => $this->type,
            'transmission' => $this->transmission,
            'plate_number' => $this->plate_number,
            'price_per_day' => (float) $this->price_per_day,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'status' => $this->status ?? 'available',
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
