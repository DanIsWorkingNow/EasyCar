@extends('layouts.app')

@section('content')
    <div class="container-ec max-w-3xl">
        <x-page-header eyebrow="Legal" :title="$title" :subtitle="'Version '.config('compliance.terms_version').' · '.config('compliance.company.name')" />
        <div class="card card-pad space-y-6 text-[15px] leading-relaxed text-white/80 [&_h2]:mt-2 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-white [&_li]:ml-5 [&_li]:list-disc [&_ul]:space-y-1.5">
            @yield('legal')
        </div>
        <p class="mt-6 text-xs text-subtle">Questions? Email <a href="mailto:{{ config('compliance.company.email') }}" class="underline">{{ config('compliance.company.email') }}</a> or call {{ config('compliance.company.phone') }}. This template should be reviewed by your legal adviser before launch.</p>
    </div>
@endsection
