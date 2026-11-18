<?php

namespace App\Payments\Exceptions;

use RuntimeException;

/**
 * Raised for gateway failures. getMessage() is safe to show customers;
 * the underlying provider response is kept in $context for logs only.
 */
class PaymentException extends RuntimeException
{
    public function __construct(string $message, public readonly array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
