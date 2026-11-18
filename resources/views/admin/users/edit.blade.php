@extends('layouts.console')

@section('title', 'Edit '.$user->name)
@section('breadcrumb', 'Admin console / Users / '.$user->name)

@section('content')
    <x-page-header eyebrow="Access" :title="$user->name" :subtitle="$user->email" :back="route('admin.users.index')" />
    @include('admin.users._form')
@endsection
