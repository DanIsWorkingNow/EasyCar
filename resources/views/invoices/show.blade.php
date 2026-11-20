@extends(auth()->user()->consoleRoute() ? 'layouts.console' : 'layouts.app')

@section('title', $invoice->title().' '.$invoice->number)

@section('content')
    <div class="{{ auth()->user()->consoleRoute() ? '' : 'container-ec' }} max-w-4xl">
        <x-page-header :eyebrow="$invoice->title()" :title="$invoice->number" :subtitle="'Issued '.$invoice->issued_at->format('d M Y')" :back="auth()->user()->consoleRoute() ? url()->previous() : route('bookings.show', $invoice->booking)" class="print:hidden">
            <x-slot:actions>
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-primary"><x-icon name="download" class="size-4" /> Download PDF</a>
                <button type="button" onclick="window.print()" class="btn btn-outline">Print</button>
            </x-slot:actions>
        </x-page-header>
        <div class="rounded-xl bg-white p-6 shadow-2xl sm:p-10 print:p-0 print:shadow-none">
            @include('invoices._document')
        </div>
    </div>
@endsection
