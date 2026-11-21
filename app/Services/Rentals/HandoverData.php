<?php

namespace App\Services\Rentals;

use Illuminate\Http\UploadedFile;

/**
 * What staff record at a pickup or return handover.
 */
final readonly class HandoverData
{
    /**
     * @param  array<string, bool>  $checklist  checklist key => passed
     * @param  array<string, string>  $damages  diagram zone => damage kind
     * @param  UploadedFile[]  $photos
     * @param  string|null  $signature  PNG data URL from the signature pad
     * @param  array{method: string, reference: ?string}|null  $counterPayment  money taken at the desk
     * @param  array{method: string, reference: ?string}|null  $deposit  security deposit taken at pick-up
     */
    public function __construct(
        public int $odometer,
        public int $fuel,
        public array $checklist = [],
        public array $damages = [],
        public ?string $notes = null,
        public array $photos = [],
        public ?string $signature = null,
        public ?string $signedByName = null,
        public ?array $counterPayment = null,
        public ?array $deposit = null,
    ) {}
}
