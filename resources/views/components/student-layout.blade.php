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
            ['label' => 'Dashboard', 'route' => 'student.dashboard', 'match' => 'student.dashboard'],
            ['label' => 'Laporan', 'route' => 'student.daily-reports.index', 'match' => 'student.daily-reports.*'],
            ['label' => 'Izin / Sakit', 'route' => 'student.leave-requests.index', 'match' => 'student.leave-requests.*'],
            ['label' => 'Profil', 'route' => 'student.profile.show', 'match' => 'student.profile.*'],
        ];
    @endphp

    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('student.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="min-w-0"><span class="block text-sm font-extrabold tracking-wide text-sky-700">E-PKL</span><span class="block truncate text-[11px] font-semibold tracking-wide text-slate-600 sm:text-xs">SISTEM MANAJEMEN PRAKTIK KERJA LAPANGAN</span></span>
            </a>
            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi murid">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold transition', 'bg-sky-50 text-sky-700' => request()->routeIs($link['match']), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs($link['match'])])>{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <p class="max-w-24 truncate text-right text-xs font-semibold text-slate-700 sm:max-w-40 sm:text-sm">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}" class="hidden md:block">@csrf<button type="submit" class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Keluar</button></form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 pb-28 pt-5 sm:px-6 sm:pt-8 md:pb-8">
        @if (session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('status') }}</div>@endif
        <x-form-errors />
        {{ $slot }}
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-2 shadow-[0_-8px_24px_rgba(15,23,42,.08)] backdrop-blur md:hidden" aria-label="Navigasi utama murid">
        <div class="mx-auto grid max-w-lg grid-cols-5 gap-1">
            @foreach ([
                ['Dashboard', 'student.dashboard', 'student.dashboard', '⌂'],
                ['Laporan', 'student.daily-reports.index', 'student.daily-reports.*', '▤'],
                ['Izin / Sakit', 'student.leave-requests.index', 'student.leave-requests.*', '✓'],
                ['Profil', 'student.profile.show', 'student.profile.*', '●'],
            ] as [$label, $route, $match, $icon])
                <a href="{{ route($route) }}" @class(['flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-xl px-1 text-[11px] font-semibold', 'bg-sky-50 text-sky-700' => request()->routeIs($match), 'text-slate-500' => ! request()->routeIs($match)])><span class="text-lg leading-none" aria-hidden="true">{{ $icon }}</span>{{ $label }}</a>
            @endforeach
            <details class="group relative">
                <summary class="flex min-h-14 cursor-pointer list-none flex-col items-center justify-center gap-0.5 rounded-xl px-1 text-[11px] font-semibold text-slate-500"><span class="text-lg leading-none" aria-hidden="true">•••</span>Menu</summary>
                <div class="absolute bottom-16 right-0 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                    <a href="{{ route('student.history') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Riwayat</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="min-h-11 w-full rounded-xl px-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">Keluar</button></form>
                </div>
            </details>
        </div>
    </nav>
</body>
</html>
