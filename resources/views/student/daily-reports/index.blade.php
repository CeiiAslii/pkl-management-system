<x-student-layout title="Laporan Harian">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-sm text-slate-500">Catat kegiatan PKL setiap hari.</p></div>
        <a href="{{ route('student.daily-reports.create') }}" class="rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-800">Tambah laporan</a>
    </div>
    <div class="grid gap-3 md:hidden">
        @forelse ($dailyReports as $dailyReport)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-900">{{ $dailyReport->report_date->translatedFormat('d M Y') }}</p>
                <p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ str($dailyReport->activity_description)->limit(180) }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-2 text-sm">
                    @if ($dailyReport->hasAvailableActivityPhoto())<a href="{{ route('student.daily-reports.photo', $dailyReport) }}" class="inline-flex min-h-11 items-center font-semibold text-sky-700">Lihat foto</a>@elseif($dailyReport->had_photo)<span class="text-slate-500">Foto telah diarsipkan</span>@else<span class="text-slate-400">Tidak ada foto</span>@endif
                    <a href="{{ route('student.daily-reports.edit', $dailyReport) }}" class="inline-flex min-h-11 items-center font-semibold text-sky-700">Ubah</a>
                    <form method="POST" action="{{ route('student.daily-reports.destroy', $dailyReport) }}" onsubmit="return confirm('Hapus laporan harian ini?')">@csrf @method('DELETE')<button class="min-h-11 font-semibold text-rose-700">Hapus</button></form>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">Belum ada laporan harian. Tambahkan laporan kegiatan PKL pertama Anda.</p>
        @endforelse
    </div>
    <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Tanggal</th><th class="px-5 py-4">Kegiatan</th><th class="px-5 py-4">Lampiran</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($dailyReports as $dailyReport)<tr><td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $dailyReport->report_date->translatedFormat('d M Y') }}</td><td class="max-w-xl px-5 py-4 text-slate-600">{{ str($dailyReport->activity_description)->limit(160) }}</td><td class="px-5 py-4">@if($dailyReport->hasAvailableActivityPhoto())<a href="{{ route('student.daily-reports.photo', $dailyReport) }}" class="font-semibold text-sky-700 hover:text-sky-900">Lihat foto</a>@elseif($dailyReport->had_photo)<span class="text-slate-500">Foto telah diarsipkan</span>@else<span class="text-slate-400">Tidak ada foto</span>@endif</td><td class="px-5 py-4"><div class="flex justify-end gap-3"><a href="{{ route('student.daily-reports.edit', $dailyReport) }}" class="font-semibold text-sky-700">Ubah</a><form method="POST" action="{{ route('student.daily-reports.destroy', $dailyReport) }}" onsubmit="return confirm('Hapus laporan harian ini?')">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada laporan harian. Tambahkan laporan kegiatan PKL pertama Anda.</td></tr>@endforelse</tbody></table></div>
    </section>
    <div class="mt-5">{{ $dailyReports->links() }}</div>
</x-student-layout>
