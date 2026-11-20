<?php

namespace App\Http\Controllers;

use App\Models\DriverProfile;
use App\Services\DriverVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverVerificationController extends Controller
{
    public function show(Request $request)
    {
        return view('verification.show', ['profile' => $request->user()->driverProfile]);
    }

    public function store(Request $request, DriverVerificationService $verification)
    {
        $profile = $request->user()->driverProfile()->first();
        $maxKb = (int) config('compliance.document_max_kb', 8192);
        $minAge = (int) config('compliance.min_driver_age', 21);
        $file = "file|mimes:jpg,jpeg,png,webp,heic,pdf|max:{$maxKb}";

        $data = $request->validate([
            'licence_number' => 'required|string|max:30',
            'licence_expiry' => 'required|date|after:today',
            'date_of_birth' => "required|date|before_or_equal:-{$minAge} years|after:1920-01-01",
            'id_type' => ['required', Rule::in(array_keys(DriverProfile::ID_TYPES))],
            'id_number' => 'required|string|max:30',
            'phone' => 'nullable|string|max:30',
            // Required on first submission; optional when updating.
            'licence_front' => ($profile ? 'nullable|' : 'required|').$file,
            'licence_back' => 'nullable|'.$file,
            'id_document' => ($profile ? 'nullable|' : 'required|').$file,
            'consent' => 'accepted',
        ], [
            'date_of_birth.before_or_equal' => "You must be at least {$minAge} to rent with EasyCar.",
            'licence_expiry.after' => 'Your licence has expired. Please renew it before booking.',
            'consent.accepted' => 'Please agree to us processing your documents for verification.',
        ]);

        $verification->submit($request->user(), $data, $request->only(['licence_front', 'licence_back', 'id_document']));

        return redirect()->route('verification.show')->with('success', 'Documents submitted. We usually verify within one business day.');
    }

    /**
     * Streams a verification document to the owner or to staff/admins.
     */
    public function document(Request $request, DriverProfile $profile, string $document)
    {
        abort_unless($request->user()->id === $profile->user_id || Gate::allows('verify-drivers'), 403);
        abort_unless(array_key_exists($document, DriverProfile::DOCUMENTS), 404);

        $path = $profile->documentPath($document);
        $disk = Storage::disk(DriverVerificationService::DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
