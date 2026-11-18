<?php

namespace App\Payments\Data;

final readonly class CheckoutSession
{
    public function __construct(
        public string $reference,
        public string $redirectUrl,
        public array $meta = [],
    ) {}
}
