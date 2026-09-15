@props(['title'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — E-PKL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800">
    @php
        $links = [
            ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'match' => 'teacher.dashboard'],
            ['label' => 'Kehadiran', 'route' => 'teacher.attendance', 'match' => 'teacher.attendance'],
            ['label' => 'Laporan Harian', 'route' => 'teacher.daily-reports.index', 'match' => 'teacher.daily-reports.*'],
            ['label' => 'Izin / Sakit', 'route' => 'teacher.leave-requests.index', 'match' => 'teacher.leave-requests.*'],
            ['label' => 'Rekap', 'route' => 'teacher.recap', 'match' => 'teacher.recap'],
            ['label' => 'Catatan Murid', 'route' => 'teacher.notes.index', 'match' => 'teacher.notes.*'],
        ];
    @endphp
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-3 py-2 sm:gap-4 sm:px-6 sm:py-3">
            <a href="{{ route('teacher.dashboard') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
                <span class="min-w-0"><span class="block text-sm font-extrabold leading-4 tracking-wide text-sky-700">E-PKL</span><span class="block truncate text-[10px] font-semibold leading-4 text-slate-600 sm:text-xs">SISTEM MANAJEMEN PRAKTIK KERJA LAPANGAN</span></span>
            </a>
            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi guru">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition', 'bg-sky-50 text-sky-700' => request()->routeIs($link['match']), 'text-slate-600 hover:bg-slate-50' => ! request()->routeIs($link['match'])])>{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="flex min-w-0 items-center gap-2">
                <span class="max-w-24 truncate rounded-full bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700 sm:max-w-40 sm:px-3 sm:text-sm">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">@csrf<button class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100">Keluar</button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-3 pb-[calc(5.75rem+env(safe-area-inset-bottom))] pt-4 sm:px-6 sm:pt-5 lg:pb-6">
        <div class="mb-4"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">Panel Guru</p><h1 class="mt-0.5 text-xl font-bold text-slate-900 sm:text-2xl">{{ $title }}</h1></div>
        @if (session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
        <x-form-errors />
        {{ $slot }}
    </main>
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-1.5 shadow-[0_-8px_24px_rgba(15,23,42,.08)] backdrop-blur lg:hidden" aria-label="Navigasi utama guru">
        <div class="mx-auto grid max-w-lg grid-cols-5 gap-1">
            <x-teacher.mobile-nav-link route="teacher.dashboard" match="teacher.dashboard" label="Dashboard" icon="⌂" />
            <x-teacher.mobile-nav-link route="teacher.daily-reports.index" match="teacher.daily-reports.*" label="Laporan" icon="▤" />
            <x-teacher.mobile-nav-link route="teacher.leave-requests.index" match="teacher.leave-requests.*" label="Izin/Sakit" icon="!" />
            <x-teacher.mobile-nav-link route="teacher.recap" match="teacher.recap" label="Rekap" icon="∑" />
            <details class="group relative">
                <summary class="flex min-h-14 cursor-pointer list-none flex-col items-center justify-center gap-1 rounded-xl px-1 text-[10px] font-bold leading-none text-slate-500 transition hover:bg-slate-50 sm:text-[11px]"><span class="grid h-5 w-5 place-items-center text-lg leading-none" aria-hidden="true">•••</span>Menu</summary>
                <div class="absolute bottom-[calc(100%+.75rem)] right-0 w-44 max-w-[calc(100vw-1rem)] rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                    <a href="{{ route('teacher.attendance') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kehadiran</a>
                    <a href="{{ route('teacher.notes.index') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Catatan</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="min-h-11 w-full rounded-xl px-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">Keluar</button></form>
                </div>
            </details>
        </div>
    </nav>
</body>
</html>
