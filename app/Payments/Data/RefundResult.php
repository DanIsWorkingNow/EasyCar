<?php

namespace App\Payments\Data;

final readonly class RefundResult
{
    public function __construct(
        public bool $completed,
        public ?string $reference = null,
        public array $meta = [],
    ) {}
}
