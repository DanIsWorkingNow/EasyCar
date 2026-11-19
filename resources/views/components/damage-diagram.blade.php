@props([
    'damages' => [],      // zone => kind (this inspection)
    'previous' => [],     // zone => kind recorded at pick-up (shown as reference on return)
    'interactive' => false,
])
@php
    // Top-down car, front at the top. [shape, attributes, label x, label y]
    $zones = [
        'front_bumper' => ['rect', 'x="52" y="18" width="136" height="26" rx="13"', 120, 31],
        'bonnet' => ['rect', 'x="58" y="48" width="124" height="74" rx="12"', 120, 85],
        'windscreen' => ['polygon', 'points="64,126 176,126 166,160 74,160"', 120, 143],
        'roof' => ['rect', 'x="74" y="164" width="92" height="110" rx="8"', 120, 219],
        'rear_window' => ['polygon', 'points="74,278 166,278 176,310 64,310"', 120, 294],
        'boot' => ['rect', 'x="58" y="314" width="124" height="62" rx="12"', 120, 345],
        'rear_bumper' => ['rect', 'x="52" y="380" width="136" height="26" rx="13"', 120, 393],
        'left_front' => ['rect', 'x="22" y="108" width="34" height="112" rx="10"', 39, 164],
        'left_rear' => ['rect', 'x="22" y="224" width="34" height="112" rx="10"', 39, 280],
        'right_front' => ['rect', 'x="184" y="108" width="34" height="112" rx="10"', 201, 164],
        'right_rear' => ['rect', 'x="184" y="224" width="34" height="112" rx="10"', 201, 280],
        'wheel_lf' => ['rect', 'x="6" y="78" width="14" height="50" rx="6"', 13, 103],
        'wheel_lr' => ['rect', 'x="6" y="318" width="14" height="50" rx="6"', 13, 343],
        'wheel_rf' => ['rect', 'x="220" y="78" width="14" height="50" rx="6"', 227, 103],
        'wheel_rr' => ['rect', 'x="220" y="318" width="14" height="50" rx="6"', 227, 343],
    ];
    $fill = ['scratch' => '#f59e0b', 'dent' => '#f97316', 'crack' => '#ef4444'];
    $letter = ['scratch' => 'S', 'dent' => 'D', 'crack' => 'C'];
    $labels = \App\Models\VehicleInspection::ZONES;
    $kindLabels = \App\Models\VehicleInspection::DAMAGE_KINDS;
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}>
    <svg viewBox="0 0 240 424" class="h-auto w-full max-w-[260px] select-none" role="img" aria-label="Damage diagram">
        <rect x="18" y="12" width="204" height="400" rx="70" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="2" />
        <text x="120" y="9" text-anchor="middle" font-size="8" fill="#8c8c8c" font-family="Inter, sans-serif" letter-spacing="2">FRONT</text>
        <text x="120" y="422" text-anchor="middle" font-size="8" fill="#8c8c8c" font-family="Inter, sans-serif" letter-spacing="2">REAR</text>
        @foreach ($zones as $zone => [$shape, $attrs, $lx, $ly])
            @php
                $kind = $damages[$zone] ?? null;
                $prev = $previous[$zone] ?? null;
                $isNew = $kind && $prev !== $kind;
                $title = $labels[$zone].($kind ? ' — '.$kindLabels[$kind] : ($interactive ? ' — tap to mark damage' : ' — no damage'));
            @endphp
            <g @if ($interactive) wire:click="cycleDamage('{{ $zone }}')" role="button" tabindex="0" aria-label="{{ $title }}" @keydown.enter.prevent="$wire.cycleDamage('{{ $zone }}')" class="cursor-pointer outline-none [&:focus-visible>*:first-child]:stroke-white" @endif>
                <title>{{ $title }}</title>
                <{{ $shape }} {!! $attrs !!}
                    fill="{{ $kind ? $fill[$kind] : 'rgba(255,255,255,0.05)' }}"
                    fill-opacity="{{ $kind ? ($isNew || ! $previous ? 0.85 : 0.35) : 1 }}"
                    stroke="{{ $prev && ! $kind ? '#8c8c8c' : ($kind ? $fill[$kind] : 'rgba(255,255,255,0.22)') }}"
                    stroke-width="1.5"
                    @if ($prev) stroke-dasharray="{{ $isNew ? '0' : '4 3' }}" @endif
                    class="transition-[fill] duration-150 {{ $interactive ? 'hover:fill-white/15' : '' }}" />
                @if ($kind)
                    <text x="{{ $lx }}" y="{{ $ly + 3 }}" text-anchor="middle" font-size="10" font-weight="800" fill="#0b0b0b" font-family="Inter, sans-serif" pointer-events="none">{{ $letter[$kind] }}</text>
                @endif
            </g>
        @endforeach
    </svg>

    <div class="mt-3 flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-[11px] text-mute">
        @foreach ($kindLabels as $k => $label)
            <span class="flex items-center gap-1.5"><span class="grid size-4 place-items-center rounded-sm text-[9px] font-black text-black" style="background: {{ $fill[$k] }}">{{ $letter[$k] }}</span>{{ $label }}</span>
        @endforeach
        @if ($previous)
            <span class="flex items-center gap-1.5"><span class="size-4 rounded-sm border border-dashed border-subtle"></span>Recorded at pick-up</span>
        @endif
    </div>
</div>
