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
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
            ['label' => 'Pendaftaran Murid', 'route' => 'admin.registrations.index', 'match' => 'admin.registrations.*'],
            ['label' => 'Data Murid', 'route' => 'admin.students.index', 'match' => 'admin.students.*'],
            ['label' => 'Data Guru', 'route' => 'admin.teachers.index', 'match' => 'admin.teachers.*'],
            ['label' => 'Jurusan', 'route' => 'admin.majors.index', 'match' => 'admin.majors.*'],
        ];
    @endphp

    <aside class="fixed inset-y-0 hidden w-72 flex-col bg-slate-950 px-5 py-7 text-slate-200 lg:flex">
        <a href="{{ route('admin.dashboard') }}" class="mb-10 block">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-300">E-PKL</p>
            <p class="mt-1 text-lg font-bold text-white">Sistem Manajemen Praktik Kerja Lapangan</p>
        </a>
        <nav class="space-y-1" aria-label="Menu admin">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @class(['block rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-sky-500 text-white shadow-lg shadow-sky-950/30' => request()->routeIs($link['match']), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs($link['match'])])>{{ $link['label'] }}</a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-2xl bg-slate-900 p-4 text-sm">
            <p class="font-semibold text-white">{{ auth()->user()->name }}</p>
            <p class="mt-1 text-slate-400">Administrator</p>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <details class="relative lg:hidden">
                    <summary class="cursor-pointer list-none rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">Menu</summary>
                    <nav class="absolute left-0 top-11 z-30 w-64 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl" aria-label="Menu admin seluler">
                        @foreach ($links as $link)
                            <a href="{{ route($link['route']) }}" @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-sky-50 text-sky-700' => request()->routeIs($link['match']), 'text-slate-600 hover:bg-slate-50' => ! request()->routeIs($link['match'])])>{{ $link['label'] }}</a>
                        @endforeach
                    </nav>
                </details>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Panel Admin</p>
                    <h1 class="text-lg font-bold text-slate-900 sm:text-xl">{{ $title }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">Admin</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4 sm:p-7">
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('status') }}</div>
            @endif
            <x-form-errors />
            {{ $slot }}
        </main>
    </div>
</body>
</html>
