@extends('layouts.console')

@section('title', 'Branch bookings')
@section('breadcrumb', 'Staff console / Bookings')

@section('content')
    <x-page-header :eyebrow="auth()->user()->branch?->name" title="Branch bookings" subtitle="Bookings for cars at your branch." />

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-stat label="Total" :value="$stats['total']" />
        <x-stat label="Pending" :value="$stats['pending']" tone="text-amber-300" />
        <x-stat label="Approved" :value="$stats['approved']" tone="text-match" />
        <x-stat label="Upcoming pick-ups" :value="$stats['upcoming']" tone="text-sky-300" />
    </div>

    @include('partials.booking-table', [
        'showRoute' => 'staff.bookings.show',
        'approveRoute' => 'staff.bookings.approve',
        'rejectRoute' => 'staff.bookings.reject',
        'bulkRoute' => 'staff.bookings.bulk-approve',
        'filterRoute' => 'staff.bookings.index',
        'showPaymentFilter' => false,
    ])
@endsection
