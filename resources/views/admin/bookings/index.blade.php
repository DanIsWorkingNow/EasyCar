@extends('layouts.console')

@section('title', 'Bookings')
@section('breadcrumb', 'Admin console / Bookings')

@section('content')
    <x-page-header eyebrow="Operations" title="Bookings" subtitle="Review, approve and export bookings across every branch.">
        <x-slot:actions>
            <a href="{{ route('bookings.export', request()->query()) }}" class="btn btn-outline"><x-icon name="download" class="size-4" /> Export CSV</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
        <x-stat label="Total" :value="number_format($stats['total'])" />
        <x-stat label="Pending" :value="$stats['pending']" tone="text-amber-300" />
        <x-stat label="Approved" :value="$stats['approved']" tone="text-match" />
        <x-stat label="Rejected" :value="$stats['rejected']" tone="text-red-400" />
        <x-stat label="Awaiting payment" :value="$stats['unpaid']" tone="text-sky-300" />
    </div>

    @include('partials.booking-table', [
        'showRoute' => 'admin.bookings.show',
        'approveRoute' => 'bookings.approve',
        'rejectRoute' => 'bookings.reject',
        'bulkRoute' => 'bookings.bulk-approve',
        'filterRoute' => 'admin.bookings.index',
        'showPaymentFilter' => true,
    ])
@endsection
