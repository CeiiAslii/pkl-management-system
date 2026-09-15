@extends('layouts.auth')

@section('title', 'Pendaftaran Murid')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        <h1 class="text-xl font-bold text-slate-950">Pendaftaran Murid</h1>
        <div class="mt-5 grid gap-4">
            <x-form.input label="Nama lengkap" name="name" required maxlength="255" autocomplete="name" />
            <x-form.input label="Email" name="email" type="email" required maxlength="255" autocomplete="email" />
            <x-form.select label="Jurusan" name="major_id" required>
                <option value="">Pilih jurusan</option>
                @foreach ($majors as $major)<option value="{{ $major->id }}" @selected((string) old('major_id') === (string) $major->id)>{{ $major->code }} — {{ $major->name }}</option>@endforeach
            </x-form.select>
            <x-form.input label="Kata sandi" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" help="Kata sandi minimal 12 karakter." />
        </div>
        <button type="submit" class="mt-5 min-h-11 w-full rounded-xl bg-sky-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-300">Daftar sebagai murid</button>
        <p class="mt-3 text-center text-sm text-slate-600">Sudah punya akun? <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold text-sky-700 hover:text-sky-900">Masuk</a></p>
    </form>
@endsection
