@props(['route', 'match', 'label', 'icon'])

@php($active = request()->routeIs($match))

<a href="{{ route($route) }}" @if ($active) aria-current="page" @endif @class([
    'flex min-h-14 min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-1 text-[10px] font-bold leading-none transition sm:text-[11px]',
    'bg-sky-50 text-sky-700' => $active,
    'text-slate-500 hover:bg-slate-50 hover:text-slate-700' => ! $active,
])>
    <span class="grid h-5 w-5 place-items-center text-lg leading-none" aria-hidden="true">{{ $icon }}</span>
    <span class="truncate">{{ $label }}</span>
</a>
