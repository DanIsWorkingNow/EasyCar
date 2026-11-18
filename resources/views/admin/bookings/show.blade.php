@extends('layouts.console')

@section('title', 'Booking '.$booking->reference)
@section('breadcrumb', 'Admin console / Bookings / '.$booking->reference)

@section('content')
    @include('partials.booking-review', [
        'approveRoute' => 'bookings.approve',
        'rejectRoute' => 'bookings.reject',
        'backRoute' => 'admin.bookings.index',
        'paymentRoute' => 'admin.payments.show',
    ])
@endsection
