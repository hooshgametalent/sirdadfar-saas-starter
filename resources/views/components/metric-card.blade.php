@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'teal', 'href' => null])

@php
    $toneClass = match ($tone) {
        'sky' => 'bg-sky-50 text-sky-700',
        'violet' => 'bg-violet-50 text-violet-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        default => 'bg-teal-50 text-teal-700',
    };
@endphp

<article {{ $attributes->class(['card flex items-start gap-4']) }}>
    @if ($icon)
        <div aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-xl text-xl {{ $toneClass }}">{{ $icon }}</div>
    @endif
    <div>
        <p class="text-sm text-slate-600">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
        @if ($href)
            <a href="{{ $href }}" class="link mt-2 inline-block text-sm">مشاهده</a>
        @endif
    </div>
</article>
