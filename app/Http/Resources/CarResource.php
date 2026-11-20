<?php

namespace App\Http\Resources;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SUPERSEDES the CarResource shipped in the API kit — adds plate_number
 * (FR-CAR-06).
 *
 * @mixin Car
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
            'year' => $this->year,
            'seats' => $this->seats,
            'fuel_type' => $this->fuel_type,
            'color' => $this->paint_color,
            'photo_url' => $this->photo_url,
            'model_3d_url' => $this->model_3d_url,
            'status' => $this->status ?? 'available',
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
