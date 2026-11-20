<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const INVOICE = 'invoice';

    public const CREDIT_NOTE = 'credit_note';

    protected $fillable = [
        'kind', 'year', 'sequence', 'number', 'payment_id', 'booking_id', 'user_id', 'original_invoice_id',
        'currency', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'lines', 'seller', 'billed_to', 'issued_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'lines' => 'array',
        'seller' => 'array',
        'billed_to' => 'array',
        'issued_at' => 'datetime',
    ];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_invoice_id');
    }

    public function isCreditNote(): bool
    {
        return $this->kind === self::CREDIT_NOTE;
    }

    public function title(): string
    {
        return $this->isCreditNote() ? 'Credit note' : 'Tax invoice';
    }

    public function filename(): string
    {
        return $this->number.'.pdf';
    }
}
