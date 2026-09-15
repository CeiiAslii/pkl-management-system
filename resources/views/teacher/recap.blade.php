<x-teacher-layout title="Rekap Murid">
    <x-teacher.filter-bar :$majors>
        @if (request('student_profile_id'))<input type="hidden" name="student_profile_id" value="{{ request('student_profile_id') }}">@endif
        @if (request('date_from'))<input type="hidden" name="date_from" value="{{ request('date_from') }}">@endif
        @if (request('date_to'))<input type="hidden" name="date_to" value="{{ request('date_to') }}">@endif
    </x-teacher.filter-bar>

    @if ($student)
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row"><div><p class="text-xs text-slate-500 sm:text-sm">Rekap murid</p><h2 class="text-lg font-bold text-slate-900 sm:text-xl">{{ $student->user->name }}</h2></div><div class="flex flex-wrap items-center gap-2"><a href="{{ route('student-recaps.export', ['studentProfile' => $student, 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white">Download Excel</a><a href="{{ route('teacher.recap', request()->except(['student_profile_id', 'date_from', 'date_to'])) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-sky-700">Kembali ke daftar</a></div></div>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3"><div><dt class="text-slate-500">Email</dt><dd class="font-semibold">{{ $student->user->email }}</dd></div><div><dt class="text-slate-500">Jurusan</dt><dd class="font-semibold">{{ $student->user->major?->code ?? '—' }}</dd></div><div><dt class="text-slate-500">Tempat PKL</dt><dd class="font-semibold">{{ $student->pkl_place_name ?? '—' }}</dd></div><div class="sm:col-span-2"><dt class="text-slate-500">Lokasi PKL</dt><dd><x-pkl-map-link :profile="$student" /></dd></div></dl>
        </section>
        <form method="GET" class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
            <input type="hidden" name="student_profile_id" value="{{ $student->id }}">
            <input type="hidden" name="student" value="{{ request('student') }}">
            <input type="hidden" name="major" value="{{ request('major') }}">
            <label class="grid flex-1 gap-1 text-sm font-semibold text-slate-700">Tanggal mulai<input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 font-normal"></label>
            <label class="grid flex-1 gap-1 text-sm font-semibold text-slate-700">Tanggal selesai<input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 font-normal"></label>
            <button class="rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-bold text-white">Terapkan Rentang</button>
        </form>
        @php($leaves = $student->leaveRequests)
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Total laporan', $student->dailyReports->count()], ['Total hadir', $student->attendances->count()], ['Total izin', $leaves->where('type', 'izin')->count()], ['Total sakit', $leaves->where('type', 'sakit')->count()]] as [$label, $value])
                <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>
            @endforeach
        </div>
        <div class="mt-5 grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-bold">Laporan harian</h3>@forelse ($student->dailyReports as $report)<div class="border-t border-slate-100 py-3 first:border-0"><div class="flex justify-between gap-3"><p class="font-semibold">{{ $report->report_date->format('d M Y') }}</p><span class="text-xs text-slate-500">{{ $report->activity_photo_path ? 'Ada foto' : 'Tanpa foto' }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $report->activity_description }}</p><p class="mt-1 text-xs text-slate-400">Dikirim {{ $report->created_at->format('d M Y H:i') }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada laporan.</p>@endforelse</section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-bold">Izin / sakit</h3>@forelse ($student->leaveRequests as $leave)<div class="border-t border-slate-100 py-3 first:border-0"><p class="font-semibold">{{ $leave->requested_for->format('d M Y') }} · {{ ucfirst($leave->type) }}</p><p class="mt-1 text-sm text-slate-600">{{ $leave->reason }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$leave->status] }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada permohonan.</p>@endforelse</section>
        </div>
        <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <h3 class="border-b border-slate-200 px-5 py-4 font-bold">Absensi</h3>
            <div class="space-y-3 p-3 md:hidden" data-mobile-list="recap-attendance">
                @forelse ($student->attendances as $attendance)
                    <x-teacher.mobile-card class="shadow-none">
                        <div class="flex items-center justify-between gap-3"><p class="font-bold text-slate-900">{{ $attendance->attendance_date->format('d M Y') }}</p><span class="text-xs font-semibold text-slate-500">{{ ucfirst($attendance->status) }}</span></div>
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-sm"><div><dt class="text-xs text-slate-500">Jam masuk</dt><dd class="font-semibold">{{ $attendance->check_in_at->format('H:i') }}</dd></div><div><dt class="text-xs text-slate-500">Jam pulang</dt><dd class="font-semibold">{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</dd></div><div class="col-span-2"><dt class="text-xs text-slate-500">Lokasi</dt><dd class="mt-0.5 break-words text-slate-700">{{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }}@if ($attendance->check_out_at)<br>{{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }}@endif</dd></div></dl>
                    </x-teacher.mobile-card>
                @empty
                    <x-teacher.empty-state message="Belum ada riwayat absensi." />
                @endforelse
            </div>
            <div class="hidden overflow-x-auto md:block" data-desktop-table="recap-attendance"><table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Tanggal', 'Jam masuk', 'Jam pulang', 'Status', 'Lokasi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($student->attendances as $attendance)
                        <tr><td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $attendance->attendance_date->format('d M Y') }}</td><td class="px-4 py-3">{{ $attendance->check_in_at->format('H:i') }}</td><td class="px-4 py-3">{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</td><td class="px-4 py-3">{{ ucfirst($attendance->status) }}</td><td class="min-w-52 px-4 py-3">{{ $attendance->check_in_latitude }}, {{ $attendance->check_in_longitude }}@if ($attendance->check_out_at)<br>{{ $attendance->check_out_latitude }}, {{ $attendance->check_out_longitude }}@endif</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat absensi.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-bold">Catatan guru</h3>@forelse ($student->teacherNotes as $note)<div class="border-t border-slate-100 py-3 first:border-0"><p class="text-xs text-slate-500">{{ $note->created_at->format('d M Y') }} · {{ $note->teacherProfile->user->name }}</p><p class="mt-1 text-sm">{{ $note->content }}</p></div>@empty<p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Belum ada catatan.</p>@endforelse</section>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="space-y-3 p-3 md:hidden" data-mobile-list="recap-students">
                @forelse ($students as $item)
                    <x-teacher.mobile-card>
                        <div><h2 class="truncate font-bold text-slate-900">{{ $item->user->name }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ $item->user->major?->code ?? 'Tanpa jurusan' }}</p></div>
                        <p class="mt-2 truncate text-sm text-slate-600">{{ $item->pkl_place_name ?? 'Tempat PKL belum diisi' }}</p><x-pkl-map-link :profile="$item" />
                        <dl class="mt-3 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center"><div><dt class="text-[11px] text-slate-500">Laporan</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $item->daily_reports_count }}</dd></div><div><dt class="text-[11px] text-slate-500">Izin</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $item->permission_count }}</dd></div><div><dt class="text-[11px] text-slate-500">Sakit</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $item->sick_count }}</dd></div></dl>
                        <div class="mt-3 grid grid-cols-2 gap-2"><a href="{{ route('teacher.recap', array_merge(request()->query(), ['student_profile_id' => $item->id])) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-700 px-2 text-center text-sm font-bold text-white">Lihat Rekap</a><a href="{{ route('student-recaps.export', $item) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-600 px-2 text-center text-sm font-bold text-emerald-700">Download Excel</a></div>
                    </x-teacher.mobile-card>
                @empty
                    <x-teacher.empty-state message="Belum ada murid aktif." />
                @endforelse
            </div>
            <div class="hidden overflow-x-auto md:block" data-desktop-table="recap-students"><table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Nama', 'Jurusan', 'Tempat PKL', 'Jumlah laporan', 'Izin', 'Sakit', 'Aksi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $item)
                            <tr><td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $item->user->name }}</td><td class="px-4 py-3">{{ $item->user->major?->code ?? '—' }}</td><td class="min-w-40 px-4 py-3">{{ $item->pkl_place_name ?? '—' }} <x-pkl-map-link :profile="$item" /></td><td class="px-4 py-3 text-center">{{ $item->daily_reports_count }}</td><td class="px-4 py-3 text-center">{{ $item->permission_count }}</td><td class="px-4 py-3 text-center">{{ $item->sick_count }}</td><td class="whitespace-nowrap px-4 py-3"><div class="flex gap-3"><a href="{{ route('teacher.recap', array_merge(request()->query(), ['student_profile_id' => $item->id])) }}" class="font-semibold text-sky-700">Lihat Rekap</a><a href="{{ route('student-recaps.export', $item) }}" class="font-semibold text-emerald-700">Download Excel</a></div></td></tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada murid aktif.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($students->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $students->links() }}</div>@endif
        </section>
    @endif
</x-teacher-layout>
