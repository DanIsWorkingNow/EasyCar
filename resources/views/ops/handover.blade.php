@extends('layouts.console')

@section('title', ($type === 'return' ? 'Return' : 'Pick-up').' · '.$car->name)
@section('breadcrumb', 'Operations / '.($type === 'return' ? 'Return check-in' : 'Pick-up handover'))

@section('content')
    <a href="{{ route('ops.board') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-mute hover:text-white"><x-icon name="arrow-left" class="size-4" /> Operations board</a>
    <livewire:ops.handover-form :booking="$booking" :car="$car" :type="$type" />
@endsection
