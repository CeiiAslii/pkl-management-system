<x-teacher-layout title="Laporan Harian">
    <x-teacher.filter-bar :$majors />
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-3 p-3 md:hidden" data-mobile-list="daily-reports">
            @forelse ($reports as $report)
                <x-teacher.mobile-card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="truncate font-bold text-slate-900">{{ $report->studentProfile->user->name }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ $report->studentProfile->user->major?->code ?? 'Tanpa jurusan' }}</p></div>
                        <time class="shrink-0 text-xs font-semibold text-slate-500">{{ $report->report_date->format('d M Y') }}</time>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div><dt class="text-xs text-slate-500">Tempat PKL</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $report->studentProfile->pkl_place_name ?? '—' }} <x-pkl-map-link :profile="$report->studentProfile" /></dd></div>
                        <div><dt class="text-xs text-slate-500">Kegiatan</dt><dd class="mt-0.5 line-clamp-3 leading-5 text-slate-700">{{ $report->activity_description }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Waktu kirim</dt><dd class="mt-0.5 text-slate-700">{{ $report->created_at->format('d M Y H:i') }}</dd></div>
                    </dl>
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                        <a href="{{ route('teacher.daily-reports.show', $report) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-sky-700 px-3 text-sm font-bold text-white">Lihat detail</a>
                        @if ($report->activity_photo_path)<a href="{{ route('private.daily-reports.photo', $report) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-bold text-slate-700">Lihat foto</a>@endif
                    </div>
                </x-teacher.mobile-card>
            @empty
                <x-teacher.empty-state message="Belum ada laporan harian." />
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block" data-desktop-table="daily-reports"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Nama murid', 'Jurusan', 'Tempat PKL', 'Tanggal', 'Kegiatan', 'Foto', 'Waktu kirim', 'Aksi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reports as $report)
                        <tr class="align-top"><td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $report->studentProfile->user->name }}</td><td class="px-4 py-3">{{ $report->studentProfile->user->major?->code ?? '—' }}</td><td class="min-w-40 px-4 py-3">{{ $report->studentProfile->pkl_place_name ?? '—' }} <x-pkl-map-link :profile="$report->studentProfile" /></td><td class="whitespace-nowrap px-4 py-3">{{ $report->report_date->format('d M Y') }}</td><td class="max-w-xs px-4 py-3"><p class="line-clamp-2">{{ $report->activity_description }}</p></td><td class="px-4 py-3">@if ($report->activity_photo_path)<a href="{{ route('private.daily-reports.photo', $report) }}" target="_blank" class="font-semibold text-sky-700">Lihat</a>@else — @endif</td><td class="whitespace-nowrap px-4 py-3">{{ $report->created_at->format('d M Y H:i') }}</td><td class="whitespace-nowrap px-4 py-3"><a href="{{ route('teacher.daily-reports.show', $report) }}" class="font-semibold text-sky-700">Lihat detail</a></td></tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada laporan harian.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($reports->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $reports->links() }}</div>@endif
    </section>
</x-teacher-layout>
