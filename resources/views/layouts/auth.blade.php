<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'E-PKL') — Sistem Manajemen Praktik Kerja Lapangan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2.5 sm:px-7 sm:py-3">
            <div class="flex items-center gap-3">
                <div>
                    <p class="text-sm font-bold tracking-wide text-sky-700">E-PKL</p>
                    <p class="text-xs font-semibold tracking-wide text-slate-700">SISTEM MANAJEMEN PRAKTIK KERJA LAPANGAN</p>
                </div>
            </div>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Keluar</button>
                </form>
            @else
                @if (! request()->routeIs('login'))
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50">Masuk</a>
                @endif
            @endauth
        </div>
    </header>

    <main class="mx-auto flex min-h-[calc(100vh-65px)] max-w-5xl items-center justify-center px-4 py-5 sm:min-h-[calc(100vh-73px)] sm:px-7 sm:py-8">
        <div class="w-full max-w-md">
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif
            <x-form-errors />
            @yield('content')
        </div>
    </main>
</body>
</html>
