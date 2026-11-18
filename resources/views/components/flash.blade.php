@php
    $messages = collect([
        'success' => session('success') ?? session('status'),
        'error' => session('error'),
        'warning' => session('warning'),
    ])->filter();

    if ($errors->any() && ! $messages->has('error')) {
        $messages->put('error', $errors->count() === 1 ? $errors->first() : 'Please fix the highlighted fields and try again.');
    }

    $styles = [
        'success' => ['icon' => 'check-circle', 'ring' => 'ring-emerald-500/30', 'iconColor' => 'text-emerald-400'],
        'error' => ['icon' => 'x-circle', 'ring' => 'ring-red-500/40', 'iconColor' => 'text-red-400'],
        'warning' => ['icon' => 'warning', 'ring' => 'ring-amber-500/30', 'iconColor' => 'text-amber-400'],
    ];
@endphp

@if ($messages->isNotEmpty())
    <div class="pointer-events-none fixed inset-x-0 top-20 z-[90] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-8" aria-live="polite">
        @foreach ($messages as $type => $message)
            <div x-data="toast({{ $type === 'error' ? 9000 : 6000 }})" x-show="open" x-transition.opacity.duration.300ms
                 class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg bg-ink-800/95 px-4 py-3.5 shadow-2xl ring-1 backdrop-blur {{ $styles[$type]['ring'] }}"
                 role="{{ $type === 'error' ? 'alert' : 'status' }}">
                <x-icon :name="$styles[$type]['icon']" class="mt-0.5 size-5 {{ $styles[$type]['iconColor'] }}" />
                <p class="flex-1 text-sm leading-relaxed text-white/90">{{ $message }}</p>
                <button type="button" @click="open = false" class="-m-1 rounded p-1 text-subtle hover:text-white" aria-label="Dismiss">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>
        @endforeach
    </div>
@endif
