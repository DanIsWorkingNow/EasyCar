@extends('layouts.console')

@section('title', 'Add user')
@section('breadcrumb', 'Admin console / Users / Add')

@section('content')
    <x-page-header eyebrow="Access" title="Add staff or admin" subtitle="Customers sign up themselves from the storefront." :back="route('admin.users.index')" />
    @include('admin.users._form', ['user' => new \App\Models\User])
@endsection
