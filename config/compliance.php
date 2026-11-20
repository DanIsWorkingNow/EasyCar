<?php

/*
|--------------------------------------------------------------------------
| Trust & compliance
|--------------------------------------------------------------------------
|
| Driver verification rules, the rental agreement version customers
| accept, and the seller details printed on tax invoices.
|
*/

return [

    // Bookings can only be approved (and cars handed over) for customers
    // whose driving licence and ID have been verified by staff.
    'require_verified_driver' => (bool) env('COMPLIANCE_REQUIRE_VERIFIED_DRIVER', true),

    'min_driver_age' => (int) env('COMPLIANCE_MIN_DRIVER_AGE', 21),

    // Max size per uploaded document, in KB. Stored on the private disk.
    'document_max_kb' => 8192,

    // Bump when the Terms of Rental change; each booking records the
    // version the customer accepted.
    'terms_version' => env('COMPLIANCE_TERMS_VERSION', '2026-11'),

    'invoices' => [
        // Prices are tax-inclusive. When a rate is set (e.g. 0.08 for 8%
        // service tax), invoices show the tax portion. Confirm with your
        // tax adviser whether your rentals are taxable.
        'tax_rate' => (float) env('INVOICE_TAX_RATE', 0),
        'tax_label' => env('INVOICE_TAX_LABEL', 'SST'),
        'invoice_prefix' => env('INVOICE_PREFIX', 'INV'),
        'credit_note_prefix' => env('CREDIT_NOTE_PREFIX', 'CN'),
    ],

    // Seller details printed on every invoice (snapshotted when issued).
    'company' => [
        'name' => env('COMPANY_NAME', 'EasyCar Rental Sdn. Bhd.'),
        'registration' => env('COMPANY_REGISTRATION', ''),
        'tax_number' => env('COMPANY_TAX_NUMBER', ''),
        'address' => env('COMPANY_ADDRESS', 'Persiaran Perbandaran, Seksyen 14, 40000 Shah Alam, Selangor'),
        'email' => env('COMPANY_EMAIL', 'billing@easycar.example'),
        'phone' => env('COMPANY_PHONE', '+60 3-5511 2345'),
    ],
];
