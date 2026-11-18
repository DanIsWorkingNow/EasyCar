@props(['status'])
@php
    $enum = $status instanceof \BackedEnum ? $status : (\App\Enums\PaymentStatus::tryFrom((string) $status) ?? \App\Enums\BookingPaymentStatus::tryFrom((string) $status));
@endphp
@if ($enum)
    <x-badge :tone="$enum->tone()" {{ $attributes }}>{{ $enum->label() }}</x-badge>
@endif
