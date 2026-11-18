@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'help' => null,
    'options' => [],
    'placeholder' => null,
    'bag' => 'default',
    'rows' => 4,
])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $dotName = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->getBag($bag)->first($dotName);
    $current = old($dotName, $value);
    $classes = 'input'.($error ? ' input-error' : '');
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->get('required'))<span class="text-brand"> *</span>@endif</label>
    @endif

    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $classes }}" {{ $attributes->except(['class', 'id']) }} @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
            @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" class="{{ $classes }}" {{ $attributes->except(['class', 'id']) }} @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" @if($type !== 'password' && $type !== 'file') value="{{ $current }}" @endif placeholder="{{ $placeholder }}" class="{{ $type === 'file' ? 'block w-full text-sm text-mute file:mr-4 file:rounded-md file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-white/20' : $classes }}" {{ $attributes->except(['class', 'id']) }} @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="field-error">{{ $error }}</p>
    @elseif ($help)
        <p class="help">{{ $help }}</p>
    @endif
</div>
