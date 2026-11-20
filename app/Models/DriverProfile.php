<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's driving licence and identity document, reviewed by staff.
 */
class DriverProfile extends Model
{
    public const DOCUMENTS = [
        'licence_front' => 'Licence (front)',
        'licence_back' => 'Licence (back)',
        'id_document' => 'IC / passport',
    ];

    public const ID_TYPES = ['ic' => 'MyKad (IC)', 'passport' => 'Passport'];

    protected $fillable = [
        'user_id', 'licence_number', 'licence_expiry', 'date_of_birth', 'id_type', 'id_number', 'phone',
        'licence_front_path', 'licence_back_path', 'id_document_path',
        'status', 'rejection_reason', 'submitted_at', 'reviewed_by', 'reviewed_at',
    ];

    protected $hidden = ['licence_number', 'id_number'];

    protected $casts = [
        'licence_number' => 'encrypted',
        'id_number' => 'encrypted',
        'licence_expiry' => 'date',
        'date_of_birth' => 'date',
        'status' => VerificationStatus::class,
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::Verified;
    }

    public function ageOn(CarbonInterface $date): int
    {
        return (int) $this->date_of_birth->diffInYears($date);
    }

    public function documentPath(string $document): ?string
    {
        return match ($document) {
            'licence_front' => $this->licence_front_path,
            'licence_back' => $this->licence_back_path,
            'id_document' => $this->id_document_path,
            default => null,
        };
    }

    /** Last 4 characters only — enough for staff to match a physical card. */
    public static function mask(?string $value): string
    {
        $value = (string) $value;

        return strlen($value) <= 4 ? $value : str_repeat('•', strlen($value) - 4).substr($value, -4);
    }
}
