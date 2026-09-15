<x-teacher-layout title="Detail Laporan Harian">
    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="border-b border-slate-100 pb-3 sm:pb-4"><p class="text-xs text-slate-500 sm:text-sm">Kegiatan tanggal {{ $report->report_date->format('d M Y') }}</p><h2 class="mt-1 text-lg font-bold text-slate-900 sm:text-xl">{{ $report->studentProfile->user->name }}</h2></div>
            <h3 class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500 sm:mt-5 sm:text-sm">Kegiatan</h3>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 sm:text-base sm:leading-7">{{ $report->activity_description }}</p>
            @if ($report->activity_photo_path)<a href="{{ route('private.daily-reports.photo', $report) }}" target="_blank" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white sm:mt-5">Lihat foto kegiatan</a>@endif
        </section>
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="font-bold text-slate-900">Informasi murid</h3>
            <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-500">Jurusan</dt><dd class="font-semibold">{{ $report->studentProfile->user->major?->code ?? '—' }}</dd></div><div><dt class="text-slate-500">Tempat PKL</dt><dd class="font-semibold">{{ $report->studentProfile->pkl_place_name ?? '—' }} <x-pkl-map-link :profile="$report->studentProfile" /></dd></div><div><dt class="text-slate-500">Waktu kirim</dt><dd class="font-semibold">{{ $report->created_at->format('d M Y H:i') }}</dd></div></dl>
            <a href="{{ route('teacher.daily-reports.index') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-sky-700 sm:mt-5">← Kembali ke daftar</a>
        </aside>
    </div>
</x-teacher-layout>
