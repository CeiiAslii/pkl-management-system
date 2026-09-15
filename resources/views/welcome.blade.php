<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-PKL — Sistem Manajemen Praktik Kerja Lapangan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-white">
    <div class="relative isolate overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-gradient-to-br from-sky-800 via-slate-950 to-slate-950"></div>
        <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-6 sm:px-8"><a href="{{ route('home') }}" class="font-bold tracking-tight">E-PKL <span class="block text-xs font-medium text-sky-300">Sistem Manajemen Praktik Kerja Lapangan</span></a><nav class="flex items-center gap-2 text-sm font-semibold">@auth<a href="{{ route('account') }}" class="rounded-lg bg-white px-4 py-2.5 text-slate-900 hover:bg-sky-100">Masuk ke akun</a>@else<a href="{{ route('login') }}" class="px-3 py-2 hover:text-sky-200">Masuk</a><a href="{{ route('register') }}" class="rounded-lg bg-sky-400 px-4 py-2.5 text-slate-950 hover:bg-sky-300">Daftar murid</a>@endauth</nav></header>
        <main class="mx-auto grid max-w-7xl gap-12 px-5 pb-20 pt-16 sm:px-8 lg:grid-cols-[1.2fr_.8fr] lg:items-center lg:pt-24"><div><p class="inline-flex rounded-full border border-sky-400/40 bg-sky-400/10 px-3 py-1 text-sm font-semibold text-sky-200">Sistem Praktik Kerja Lapangan</p><h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight sm:text-6xl">Kelola PKL dengan lebih tertib dan terarah.</h1><p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">E-PKL membantu menghubungkan data murid, guru, jurusan, kelas, dan tempat PKL dalam satu sistem.</p><div class="mt-9 flex flex-wrap gap-3"><a href="{{ route('login') }}" class="rounded-lg bg-sky-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-sky-300">Masuk ke sistem</a><a href="{{ route('register') }}" class="rounded-lg border border-slate-600 px-5 py-3 text-sm font-bold text-white hover:border-slate-400 hover:bg-white/5">Pendaftaran murid</a></div></div><section class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl shadow-sky-950/30 backdrop-blur sm:p-8"><p class="text-sm font-semibold text-sky-200">Untuk murid baru</p><h2 class="mt-3 text-2xl font-bold">Daftar secara mandiri, lalu tunggu persetujuan admin.</h2><ol class="mt-6 space-y-4 text-sm text-slate-200"><li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-sky-400 font-bold text-slate-950">1</span>Isi nama, email, jurusan, dan kata sandi.</li><li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-sky-400 font-bold text-slate-950">2</span>Akun terdaftar dengan status menunggu persetujuan.</li><li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-sky-400 font-bold text-slate-950">3</span>Gunakan sistem setelah admin menyetujui akun.</li></ol></section></main>
    </div>
</body>
</html>
