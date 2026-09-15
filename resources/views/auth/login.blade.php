@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h1 class="text-xl font-bold text-slate-950">Masuk</h1>
        @if (($throttleSeconds ?? 0) > 0)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                <p class="font-semibold">⚠ Terlalu banyak percobaan masuk.</p>
                <p class="mt-1">Untuk keamanan akun, coba lagi dalam <span data-login-countdown>{{ $throttleSeconds }}</span> detik.</p>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
            @csrf
            <x-form.input label="Nama atau Email" name="identifier" :value="$throttledIdentifier ?? null" required maxlength="255" autocomplete="username" />
            <x-form.input label="Kata sandi" name="password" type="password" required autocomplete="current-password" />
            <button id="login-submit" type="submit" @disabled(($throttleSeconds ?? 0) > 0) class="min-h-11 w-full rounded-xl bg-sky-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300 disabled:cursor-not-allowed disabled:bg-slate-400">
                <span data-login-submit-label>{{ ($throttleSeconds ?? 0) > 0 ? 'Coba lagi dalam '.$throttleSeconds.' dtk' : 'Masuk' }}</span>
            </button>
        </form>
        <p class="mt-4 text-center text-sm"><a href="{{ route('register') }}" class="inline-flex min-h-11 items-center font-semibold text-sky-700 hover:text-sky-900">Daftar di sini</a></p>
    </section>
    @if (($throttleSeconds ?? 0) > 0)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const countdown = document.querySelector('[data-login-countdown]');
                const button = document.getElementById('login-submit');
                const label = document.querySelector('[data-login-submit-label]');
                if (!countdown || !button || !label) return;

                let seconds = Number.parseInt(countdown.textContent, 10);
                const update = () => {
                    countdown.textContent = String(Math.max(seconds, 0));
                    button.disabled = seconds > 0;
                    label.textContent = seconds > 0 ? `Coba lagi dalam ${seconds} dtk` : 'Masuk';
                    if (seconds > 0) seconds -= 1;
                };

                update();
                const timer = window.setInterval(() => {
                    update();
                    if (seconds < 0) window.clearInterval(timer);
                }, 1000);
            });
        </script>
    @endif
@endsection
