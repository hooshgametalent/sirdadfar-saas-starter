@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = is_object($status) && method_exists($status, 'label') ? $status->label() : $value;
    $tone = match ($value) {
        'pending_review', 'pending', 'draft' => 'bg-slate-100 text-slate-700 ring-slate-300',
        'open_for_offers' => 'bg-sky-50 text-sky-800 ring-sky-300',
        'assigned', 'loaded' => 'bg-amber-50 text-amber-800 ring-amber-300',
        'in_transit' => 'bg-violet-50 text-violet-800 ring-violet-300',
        'delivered', 'approved', 'accepted', 'published', 'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-300',
        'cancelled', 'rejected', 'withdrawn' => 'bg-rose-50 text-rose-800 ring-rose-300',
        default => 'bg-slate-100 text-slate-700 ring-slate-300',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $tone]) }}>
    {{ $label }}
</span>
