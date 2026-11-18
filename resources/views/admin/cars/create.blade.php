@extends('layouts.console')

@section('title', 'Add car')
@section('breadcrumb', 'Admin console / Fleet / Add car')

@section('content')
    <x-page-header eyebrow="Fleet" title="Add a car" :back="route('admin.cars.index')" />
    @include('admin.cars._form')
@endsection
