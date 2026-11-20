<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\DriverProfile;
use App\Models\User;
use App\Notifications\DriverVerificationReviewed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Driver verification (KYC). Documents are written to the private disk
 * under verifications/{user}/ and only ever streamed through an
 * authorised controller. Any resubmission goes back to "pending".
 */
class DriverVerificationService
{
    public const DISK = 'local';

    /**
     * @param  array{licence_number: string, licence_expiry: string, date_of_birth: string, id_type: string, id_number: string, phone?: ?string}  $details
     * @param  array<string, UploadedFile|null>  $files  licence_front, licence_back, id_document
     */
    public function submit(User $user, array $details, array $files): DriverProfile
    {
        return DB::transaction(function () use ($user, $details, $files) {
            $profile = $user->driverProfile()->lockForUpdate()->first() ?? new DriverProfile(['user_id' => $user->id]);
            $old = [];

            foreach (array_keys(DriverProfile::DOCUMENTS) as $document) {
                if (($file = $files[$document] ?? null) instanceof UploadedFile) {
                    $old[] = $profile->documentPath($document);
                    $profile->{$document.'_path'} = $file->store("verifications/{$user->id}", self::DISK);
                }
            }

            $profile->fill([
                'licence_number' => strtoupper(trim($details['licence_number'])),
                'licence_expiry' => $details['licence_expiry'],
                'date_of_birth' => $details['date_of_birth'],
                'id_type' => $details['id_type'],
                'id_number' => strtoupper(preg_replace('/\s+/', '', $details['id_number'])),
                'phone' => $details['phone'] ?? null,
                'status' => VerificationStatus::Pending,
                'rejection_reason' => null,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ])->save();

            // Replaced documents are deleted, not kept around (PDPA data minimisation).
            DB::afterCommit(fn () => Storage::disk(self::DISK)->delete(array_filter($old)));

            $user->setRelation('driverProfile', $profile);

            return $profile;
        });
    }

    public function approve(DriverProfile $profile, User $reviewer): void
    {
        $profile->update([
            'status' => VerificationStatus::Verified,
            'rejection_reason' => null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $profile->user->notify(new DriverVerificationReviewed($profile));
    }

    public function reject(DriverProfile $profile, User $reviewer, string $reason): void
    {
        $profile->update([
            'status' => VerificationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $profile->user->notify(new DriverVerificationReviewed($profile));
    }
}
