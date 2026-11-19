<?php

namespace App\Http\Controllers;

use App\Models\InspectionPhoto;
use App\Models\VehicleInspection;
use App\Services\Rentals\RentalOperationsService;
use Illuminate\Support\Facades\Storage;

/**
 * Handover reports. Photos and signatures live on the private disk and
 * are streamed only after the policy check — never via a public URL.
 */
class InspectionController extends Controller
{
    public function show(VehicleInspection $inspection)
    {
        $this->authorize('view', $inspection);

        $inspection->load(['booking.user', 'booking.inspections', 'booking.charges', 'car.branch', 'performer', 'photos']);
        $pickup = $inspection->isPickup() ? null : $inspection->booking->inspectionFor($inspection->car_id, VehicleInspection::PICKUP);

        return view('inspections.show', [
            'inspection' => $inspection,
            'pickup' => $pickup,
            'charges' => $inspection->isPickup()
                ? collect()
                : $inspection->booking->charges->where('car_id', $inspection->car_id)->values(),
        ]);
    }

    public function photo(VehicleInspection $inspection, InspectionPhoto $photo)
    {
        $this->authorize('view', $inspection);
        abort_unless($photo->vehicle_inspection_id === $inspection->id, 404);

        return $this->stream($photo->path);
    }

    public function signature(VehicleInspection $inspection)
    {
        $this->authorize('view', $inspection);
        abort_unless($inspection->signature_path, 404);

        return $this->stream($inspection->signature_path);
    }

    private function stream(string $path)
    {
        $disk = Storage::disk(RentalOperationsService::DISK);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
