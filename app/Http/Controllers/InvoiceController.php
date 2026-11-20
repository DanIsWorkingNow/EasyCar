<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return view('invoices.show', ['invoice' => $invoice->load(['booking', 'original', 'payment'])]);
    }

    public function pdf(Invoice $invoice, InvoiceService $invoices)
    {
        $this->authorize('view', $invoice);

        return $invoices->pdf($invoice)->download($invoice->filename());
    }
}
