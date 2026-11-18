@extends('layouts.console')

@section('title', 'Booking '.$booking->reference)
@section('breadcrumb', 'Staff console / Bookings / '.$booking->reference)

@section('content')
    @include('partials.booking-review', [
        'approveRoute' => 'staff.bookings.approve',
        'rejectRoute' => 'staff.bookings.reject',
        'backRoute' => 'staff.bookings.index',
        'paymentRoute' => null,
    ])
@endsection
