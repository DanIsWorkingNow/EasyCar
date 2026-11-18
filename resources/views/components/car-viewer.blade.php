@props(['car', 'height' => 'h-[56vh] min-h-[360px] max-h-[720px]', 'controls' => true])
@php
    $palette = collect([$car->paint_color, '#e50914', '#f5f5f5', '#111827', '#1d4ed8', '#9ca3af', '#0f766e', '#f59e0b'])->unique()->values();
@endphp
<div data-car-viewer='@json($car->viewerConfig())' data-viewer-state="idle"
     {{ $attributes->merge(['class' => "group/viewer relative isolate overflow-hidden {$height}"]) }}>
    <div data-viewer-canvas class="absolute inset-0 cursor-grab active:cursor-grabbing"></div>

    {{-- Loading --}}
    <div class="pointer-events-none absolute inset-0 grid place-items-center transition-opacity duration-500 group-data-[viewer-state=ready]/viewer:opacity-0 group-data-[viewer-state=unsupported]/viewer:hidden group-data-[viewer-state=error]/viewer:hidden">
        <div class="flex flex-col items-center gap-3 text-mute">
            <span class="size-10 animate-spin rounded-full border-2 border-white/15 border-t-brand"></span>
            <span class="text-xs font-semibold tracking-[0.2em] uppercase">Preparing 3D showroom</span>
        </div>
    </div>

    {{-- No WebGL / load error: fall back to the photo --}}
    <div class="absolute inset-0 hidden group-data-[viewer-state=error]/viewer:block group-data-[viewer-state=unsupported]/viewer:block">
        <x-car-photo :car="$car" class="object-contain" />
        <p class="absolute inset-x-0 bottom-4 text-center text-xs text-mute">3D view isn't available on this device — showing photo.</p>
    </div>

    @if ($controls)
        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-4 opacity-0 transition group-data-[viewer-state=ready]/viewer:opacity-100">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-black/55 px-3 py-1.5 text-[11px] font-semibold tracking-wide text-white/80 backdrop-blur">
                <x-icon name="cube" class="size-3.5 text-brand" /> Drag to rotate · Scroll to zoom
            </span>
            <div class="pointer-events-auto flex gap-1.5">
                @foreach (['autorotate' => ['rotate', 'Auto-rotate'], 'lights' => ['bulb', 'Headlights'], 'reset' => ['camera', 'Reset view'], 'fullscreen' => ['expand', 'Fullscreen']] as $action => [$icon, $label])
                    <button type="button" data-viewer-action="{{ $action }}" aria-pressed="false" title="{{ $label }}" aria-label="{{ $label }}"
                            class="grid size-9 place-items-center rounded-full bg-black/55 text-white/85 ring-1 ring-white/10 backdrop-blur transition hover:bg-black/80 hover:text-white aria-pressed:bg-white aria-pressed:text-black">
                        <x-icon :name="$icon" class="size-4" />
                    </button>
                @endforeach
            </div>
        </div>

        <div class="absolute inset-x-0 bottom-0 flex flex-col items-center gap-3 p-4 opacity-0 transition group-data-[viewer-state=ready]/viewer:opacity-100 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-2 rounded-full bg-black/55 p-1.5 ring-1 ring-white/10 backdrop-blur" role="group" aria-label="Paint colour">
                @foreach ($palette as $hex)
                    <button type="button" data-viewer-color="{{ $hex }}" aria-pressed="false" aria-label="Paint {{ $hex }}"
                            class="size-6 rounded-full ring-1 ring-white/25 transition hover:scale-110 aria-pressed:ring-2 aria-pressed:ring-white aria-pressed:ring-offset-2 aria-pressed:ring-offset-black"
                            style="background: {{ $hex }}"></button>
                @endforeach
            </div>
            <div class="flex items-center gap-1 rounded-full bg-black/55 p-1 text-xs font-semibold ring-1 ring-white/10 backdrop-blur" role="group" aria-label="Camera angle">
                @foreach (['front' => 'Front', 'side' => 'Side', 'rear' => 'Rear', 'top' => 'Top'] as $view => $label)
                    <button type="button" data-viewer-action="view:{{ $view }}" class="rounded-full px-3 py-1.5 text-white/75 transition hover:bg-white/10 hover:text-white">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    @endif
</div>
