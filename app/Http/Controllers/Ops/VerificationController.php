<?php

namespace App\Http\Controllers\Ops;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DriverProfile;
use App\Services\DriverVerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('verify-drivers');

        $status = $request->input('status', VerificationStatus::Pending->value);

        $profiles = DriverProfile::with(['user', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('ops.verifications.index', [
            'profiles' => $profiles,
            'status' => $status,
            'counts' => DriverProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(DriverProfile $profile)
    {
        $this->authorize('verify-drivers');

        $profile->load(['user', 'reviewer']);

        return view('ops.verifications.show', [
            'profile' => $profile,
            'upcoming' => Booking::with('cars')->where('user_id', $profile->user_id)->upcoming()->orderBy('start_date')->get(),
        ]);
    }

    public function update(Request $request, DriverProfile $profile, DriverVerificationService $verification)
    {
        $this->authorize('verify-drivers');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => 'required_if:decision,reject|nullable|string|max:500',
        ], ['reason.required_if' => 'Tell the customer what to fix.']);

        if ($data['decision'] === 'approve') {
            $verification->approve($profile, $request->user());
            $message = "{$profile->user->name} is now a verified driver.";
        } else {
            $verification->reject($profile, $request->user(), $data['reason']);
            $message = "Sent back to {$profile->user->name} with your note.";
        }

        return redirect()->route('ops.verifications.index')->with('success', $message);
    }
}
