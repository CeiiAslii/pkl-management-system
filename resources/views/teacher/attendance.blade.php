<x-teacher-layout title="Kehadiran">
    <x-teacher.filter-bar :$majors />

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3 sm:px-5 sm:py-4"><h2 class="font-bold text-slate-900">Kehadiran hari ini</h2><p class="mt-0.5 text-xs text-slate-500 sm:text-sm">{{ today()->translatedFormat('d F Y') }}</p></div>
        <div class="space-y-3 p-3 md:hidden" data-mobile-list="attendance">
            @forelse ($students as $student)
                @php($attendance = $student->attendances->first())
                <x-teacher.mobile-card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h3 class="truncate font-bold text-slate-900">{{ $student->user->name }}</h3><p class="mt-0.5 text-xs text-slate-500">{{ $student->user->major?->code ?? 'Tanpa jurusan' }}</p></div>
                        <x-teacher.status-badge :status="! $attendance ? 'absent' : ($attendance->check_out_at ? 'checked-out' : 'checked-in')" class="shrink-0" />
                    </div>
                    <p class="mt-2 truncate text-sm text-slate-600">{{ $student->pkl_place_name ?? 'Tempat PKL belum diisi' }}</p><x-pkl-map-link :profile="$student" />
                    <dl class="mt-3 grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Jam masuk</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $attendance?->check_in_at?->format('H:i') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Jam pulang</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $attendance?->check_out_at?->format('H:i') ?? '—' }}</dd></div>
                    </dl>
                    @if ($attendance)
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <details class="w-full rounded-xl border border-slate-200 px-3 sm:w-auto">
                                <summary class="flex min-h-11 cursor-pointer items-center text-sm font-bold text-sky-700">Lihat lokasi</summary>
                                <div class="border-t border-slate-100 py-3 text-xs leading-5 text-slate-600"><p><strong>Masuk:</strong> {{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }} (±{{ $attendance->check_in_accuracy }} m)</p>@if ($attendance->check_out_at)<p class="mt-2"><strong>Pulang:</strong> {{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }} (±{{ $attendance->check_out_accuracy }} m)</p>@endif</div>
                            </details>
                            <a href="{{ route('private.attendances.selfie', [$attendance, 'check-in']) }}" target="_blank" class="inline-flex min-h-11 items-center rounded-xl bg-sky-50 px-3 text-sm font-bold text-sky-700">Selfie masuk</a>
                            @if ($attendance->check_out_selfie_path)<a href="{{ route('private.attendances.selfie', [$attendance, 'check-out']) }}" target="_blank" class="inline-flex min-h-11 items-center rounded-xl bg-sky-50 px-3 text-sm font-bold text-sky-700">Selfie pulang</a>@endif
                        </div>
                    @endif
                </x-teacher.mobile-card>
            @empty
                <x-teacher.empty-state message="Belum ada murid aktif." />
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block" data-desktop-table="attendance">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Nama', 'Jurusan', 'Tempat PKL', 'Jam Masuk', 'Jam Pulang', 'Status', 'GPS', 'Aksi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                                @php($attendance = $student->attendances->first())
                        <tr class="align-top"><td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $student->user->name }}</td><td class="px-4 py-3">{{ $student->user->major?->code ?? '—' }}</td><td class="min-w-40 px-4 py-3">{{ $student->pkl_place_name ?? '—' }} <x-pkl-map-link :profile="$student" /></td><td class="whitespace-nowrap px-4 py-3">{{ $attendance?->check_in_at?->format('H:i') ?? '—' }}</td><td class="whitespace-nowrap px-4 py-3">{{ $attendance?->check_out_at?->format('H:i') ?? '—' }}</td><td class="whitespace-nowrap px-4 py-3"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-slate-100 text-slate-600' => ! $attendance, 'bg-amber-50 text-amber-700' => $attendance && ! $attendance->check_out_at, 'bg-emerald-50 text-emerald-700' => $attendance?->check_out_at])>{{ ! $attendance ? 'Belum absen' : ($attendance->check_out_at ? 'Sudah pulang' : 'Sudah masuk') }}</span></td><td class="min-w-52 px-4 py-3">@if ($attendance)<details><summary class="cursor-pointer font-semibold text-sky-700">Lihat Lokasi</summary><div class="mt-2 space-y-2 text-xs text-slate-600"><p><strong>Masuk:</strong> {{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }} (±{{ $attendance->check_in_accuracy }} m)</p>@if ($attendance->check_out_at)<p><strong>Pulang:</strong> {{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }} (±{{ $attendance->check_out_accuracy }} m)</p>@endif</div></details>@else — @endif</td><td class="whitespace-nowrap px-4 py-3">@if ($attendance)<div class="flex flex-col gap-1"><a href="{{ route('private.attendances.selfie', [$attendance, 'check-in']) }}" target="_blank" class="font-semibold text-sky-700">Selfie masuk</a>@if ($attendance->check_out_selfie_path)<a href="{{ route('private.attendances.selfie', [$attendance, 'check-out']) }}" target="_blank" class="font-semibold text-sky-700">Selfie pulang</a>@endif</div>@else — @endif</td></tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada murid aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $students->links() }}</div>@endif
    </section>
</x-teacher-layout>
