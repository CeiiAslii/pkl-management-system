<x-teacher-layout title="Izin / Sakit">
    <x-teacher.filter-bar :$majors />
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-3 p-3 md:hidden" data-mobile-list="leave-requests">
            @forelse ($requests as $leave)
                <x-teacher.mobile-card>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="truncate font-bold text-slate-900">{{ $leave->studentProfile->user->name }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ $leave->studentProfile->user->major?->code ?? 'Tanpa jurusan' }}</p></div>
                        <x-teacher.status-badge :status="$leave->status" class="shrink-0" />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">Tanggal</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $leave->requested_for->format('d M Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Jenis</dt><dd class="mt-0.5 font-semibold capitalize text-slate-800">{{ $leave->type }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-slate-500">Alasan</dt><dd class="mt-0.5 leading-5 text-slate-700">{{ $leave->reason }}</dd></div>
                    </dl>
                    @if ($leave->supporting_file_path)<a href="{{ route('private.leave-requests.file', $leave) }}" target="_blank" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-sky-700">Lihat bukti</a>@endif
                    @if ($leave->status === 'pending')
                        <form method="POST" action="{{ route('teacher.leave-requests.review', $leave) }}" class="mt-3 border-t border-slate-100 pt-3">@csrf @method('PATCH')
                            <input name="teacher_note" value="{{ old('teacher_note') }}" placeholder="Catatan guru (opsional)" class="form-control">
                            <div class="mt-2 grid grid-cols-2 gap-2"><button name="status" value="approved" data-confirm="Setujui permohonan {{ $leave->studentProfile->user->name }}?" onclick="return confirm(this.dataset.confirm)" class="min-h-11 rounded-xl bg-emerald-600 px-3 text-sm font-bold text-white">Setujui</button><button name="status" value="rejected" data-confirm="Tolak permohonan {{ $leave->studentProfile->user->name }}?" onclick="return confirm(this.dataset.confirm)" class="min-h-11 rounded-xl bg-rose-600 px-3 text-sm font-bold text-white">Tolak</button></div>
                        </form>
                    @elseif ($leave->teacher_note)
                        <p class="mt-3 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">Catatan: {{ $leave->teacher_note }}</p>
                    @endif
                </x-teacher.mobile-card>
            @empty
                <x-teacher.empty-state message="Belum ada pengajuan izin atau sakit." />
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block" data-desktop-table="leave-requests"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Murid', 'Jurusan', 'Tanggal', 'Jenis', 'Alasan', 'Bukti', 'Status', 'Aksi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $leave)
                    <tr class="align-top"><td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $leave->studentProfile->user->name }}</td><td class="px-4 py-3">{{ $leave->studentProfile->user->major?->code ?? '—' }}</td><td class="whitespace-nowrap px-4 py-3">{{ $leave->requested_for->format('d M Y') }}</td><td class="px-4 py-3 capitalize">{{ $leave->type }}</td><td class="min-w-56 px-4 py-3">{{ $leave->reason }}</td><td class="px-4 py-3">@if ($leave->supporting_file_path)<a href="{{ route('private.leave-requests.file', $leave) }}" target="_blank" class="font-semibold text-sky-700">Lihat</a>@else — @endif</td><td class="px-4 py-3"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-amber-50 text-amber-700' => $leave->status === 'pending', 'bg-emerald-50 text-emerald-700' => $leave->status === 'approved', 'bg-rose-50 text-rose-700' => $leave->status === 'rejected'])>{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$leave->status] }}</span></td>
                        <td class="min-w-64 px-4 py-3">
                            @if ($leave->status === 'pending')
                                <form method="POST" action="{{ route('teacher.leave-requests.review', $leave) }}" class="space-y-2">@csrf @method('PATCH')
                                    <input name="teacher_note" value="{{ old('teacher_note') }}" placeholder="Catatan guru (opsional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <div class="flex gap-2"><button name="status" value="approved" data-confirm="Setujui permohonan {{ $leave->studentProfile->user->name }}?" onclick="return confirm(this.dataset.confirm)" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Setujui</button><button name="status" value="rejected" data-confirm="Tolak permohonan {{ $leave->studentProfile->user->name }}?" onclick="return confirm(this.dataset.confirm)" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white">Tolak</button></div>
                                </form>
                            @else
                                <p class="text-xs text-slate-500">{{ $leave->teacher_note ?: 'Sudah ditinjau tanpa catatan.' }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada pengajuan izin atau sakit.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($requests->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $requests->links() }}</div>@endif
    </section>
</x-teacher-layout>
