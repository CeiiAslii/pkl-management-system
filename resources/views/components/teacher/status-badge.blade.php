@props(['status'])

@php
    [$label, $classes] = match ($status) {
        'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
        'rejected' => ['Ditolak', 'bg-rose-50 text-rose-700'],
        'checked-in' => ['Sudah masuk', 'bg-amber-50 text-amber-700'],
        'checked-out' => ['Sudah pulang', 'bg-emerald-50 text-emerald-700'],
        'absent' => ['Belum absen', 'bg-slate-100 text-slate-600'],
        default => ['Menunggu', 'bg-amber-50 text-amber-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-bold', $classes]) }}>{{ $label }}</span>
