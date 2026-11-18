@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg'])
{{--
    Open with: $dispatch('open-modal', { name: '{{ $name }}', ...anything })
    The detail object is available inside the slot as `payload`.
--}}
<div x-data="{ open: false, payload: {} }"
     x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === '{{ $name }}') { payload = $event.detail ?? {}; open = true; $nextTick(() => $el.querySelector('textarea, input:not([type=hidden]), select')?.focus()); }"
     x-on:close-modal.window="open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
     role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="open = false"></div>
    <div x-show="open"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
         class="card relative w-full {{ $maxWidth }} shadow-2xl">
        @if ($title)
            <div class="flex items-center justify-between border-b border-line px-6 py-4">
                <h2 class="text-lg font-semibold">{{ $title }}</h2>
                <button type="button" class="btn-quiet -mr-2 rounded-md p-1.5" @click="open = false" aria-label="Close"><x-icon name="x" /></button>
            </div>
        @endif
        <div class="p-6">{{ $slot }}</div>
    </div>
</div>
