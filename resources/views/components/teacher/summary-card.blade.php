@props(['label', 'value', 'tone' => 'sky'])

@php
    $valueClass = match ($tone) {
        'amber' => 'text-amber-700',
        'emerald' => 'text-emerald-700',
        'slate' => 'text-slate-700',
        default => 'text-sky-700',
    };
@endphp

<section {{ $attributes->class(['rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4']) }}>
    <p class="text-xs font-semibold leading-4 text-slate-500 sm:text-sm">{{ $label }}</p>
    <p class="mt-1 text-2xl font-extrabold leading-none {{ $valueClass }} sm:mt-2 sm:text-3xl">{{ $value }}</p>
</section>
