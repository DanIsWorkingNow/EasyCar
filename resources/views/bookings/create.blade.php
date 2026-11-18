@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <div class="container-ec">
        <x-page-header eyebrow="New booking" title="Plan your trip" subtitle="Live availability across every branch. Prices update as you go." :back="url()->previous() !== url()->current() ? url()->previous() : route('cars.index')" />
        <livewire:booking.car-availability-picker :preselected-car-id="$preselectedCarId" />
    </div>
@endsection
