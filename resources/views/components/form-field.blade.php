@props(['label', 'for', 'name' => null, 'hint' => null, 'required' => false])

@php
    $errorKey = $name ?? $for;
@endphp

<div {{ $attributes->class(['space-y-1']) }}>
    <label for="{{ $for }}" class="block text-sm font-medium text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="text-rose-600" aria-hidden="true">*</span>
            <span class="sr-only">(الزامی)</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p id="{{ $for }}-hint" class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p id="{{ $for }}-error" class="text-sm text-rose-700">{{ $message }}</p>
    @enderror
</div>
