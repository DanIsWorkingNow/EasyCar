<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { margin: 0; }
        /* dompdf doesn't inherit font-family into tables. */
        body, div, table, tr, td, th, span, strong { font-family: 'DejaVu Sans', sans-serif; }
    </style>
</head>
<body>
    @include('invoices._document')
</body>
</html>
