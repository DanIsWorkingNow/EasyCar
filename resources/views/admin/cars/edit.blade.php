@extends('layouts.console')

@section('title', 'Edit '.$car->name)
@section('breadcrumb', 'Admin console / Fleet / '.$car->name)

@section('content')
    <x-page-header eyebrow="Fleet" :title="'Edit '.$car->name" :back="route('admin.cars.show', $car)" />
    @include('admin.cars._form')
@endsection
