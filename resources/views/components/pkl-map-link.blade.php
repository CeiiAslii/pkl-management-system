@props(['profile'])

@if ($url = $profile?->pklMapUrl())
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" {{ $attributes->class(['inline-flex min-h-11 items-center text-sm font-semibold text-sky-700 hover:underline']) }}>Buka di Maps</a>
@else
    <span class="text-sm text-slate-500">Lokasi belum diisi</span>
@endif
