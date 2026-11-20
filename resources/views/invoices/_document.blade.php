{{-- Shared invoice body. Plain, inline-styled HTML so dompdf and browsers render it identically. --}}
@php
    $s = $invoice->seller;
    $money = fn ($v) => $invoice->currency.' '.number_format((float) $v, 2);
    $taxLabel = config('compliance.invoices.tax_label', 'SST');
@endphp
<div style="font-family: 'DejaVu Sans', Inter, Arial, sans-serif; color: #111; font-size: 12px; line-height: 1.5;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                <div style="font-size: 28px; font-weight: bold; letter-spacing: 1px; color: #e50914;">EASY<span style="color: #111;">CAR</span></div>
                <div style="margin-top: 6px; font-weight: 700;">{{ $s['name'] ?? '' }}</div>
                @if (! empty($s['registration']))<div>Company no. {{ $s['registration'] }}</div>@endif
                <div style="max-width: 280px;">{{ $s['address'] ?? '' }}</div>
                <div>{{ $s['email'] ?? '' }} · {{ $s['phone'] ?? '' }}</div>
                @if (! empty($s['tax_number']))<div>{{ $taxLabel }} reg. no. {{ $s['tax_number'] }}</div>@endif
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div style="font-size: 20px; font-weight: bold; text-transform: uppercase;">{{ $invoice->title() }}</div>
                <div style="margin-top: 6px;"><strong>No.</strong> {{ $invoice->number }}</div>
                <div><strong>Date</strong> {{ $invoice->issued_at->format('d M Y') }}</div>
                <div><strong>Booking</strong> {{ $invoice->booking?->reference }}</div>
                @if ($invoice->original)<div><strong>Refers to</strong> {{ $invoice->original->number }}</div>@endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 24px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #666;">Billed to</div>
        <div style="font-weight: 700;">{{ $invoice->billed_to['name'] ?? '' }}</div>
        <div>{{ $invoice->billed_to['email'] ?? '' }}</div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #111; color: #fff;">
                <th style="text-align: left; padding: 8px;">Description</th>
                <th style="text-align: right; padding: 8px; width: 60px;">Qty</th>
                <th style="text-align: right; padding: 8px; width: 110px;">Unit</th>
                <th style="text-align: right; padding: 8px; width: 120px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr style="border-bottom: 1px solid #e5e5e5;">
                    <td style="padding: 8px;">{{ $line['description'] }}</td>
                    <td style="padding: 8px; text-align: right;">{{ rtrim(rtrim(number_format($line['quantity'], 2), '0'), '.') }}</td>
                    <td style="padding: 8px; text-align: right;">{{ $money($line['unit_amount']) }}</td>
                    <td style="padding: 8px; text-align: right;">{{ $money($line['amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 280px; margin-left: auto; margin-top: 12px; border-collapse: collapse;">
        @if ($invoice->tax_rate > 0)
            <tr><td style="padding: 4px 8px;">Subtotal (excl. {{ $taxLabel }})</td><td style="padding: 4px 8px; text-align: right;">{{ $money($invoice->subtotal) }}</td></tr>
            <tr><td style="padding: 4px 8px;">{{ $taxLabel }} {{ rtrim(rtrim(number_format($invoice->tax_rate * 100, 2), '0'), '.') }}%</td><td style="padding: 4px 8px; text-align: right;">{{ $money($invoice->tax_amount) }}</td></tr>
        @endif
        <tr style="border-top: 2px solid #111;">
            <td style="padding: 8px; font-weight: bold;">{{ $invoice->isCreditNote() ? 'Total credited' : 'Total paid' }}</td>
            <td style="padding: 8px; text-align: right; font-weight: bold; font-size: 14px;">{{ $money($invoice->total) }}</td>
        </tr>
    </table>

    <div style="margin-top: 28px; font-size: 10px; color: #666;">
        @if ($invoice->payment)
            {{ $invoice->isCreditNote() ? 'Refunded via' : 'Paid via' }} {{ $invoice->payment->gatewayLabel() }}@if ($invoice->payment->gateway_reference) · ref {{ $invoice->payment->gateway_reference }}@endif.
        @endif
        Prices include applicable taxes. This is a computer-generated document; no signature is required.
    </div>
</div>
