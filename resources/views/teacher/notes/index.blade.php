<x-teacher-layout title="Catatan Murid">
    <x-teacher.filter-bar :$majors search-name="search" placeholder="Cari murid lalu Enter" />

    <section class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="font-bold">Tambah catatan</h2>
        <form method="POST" action="{{ route('teacher.notes.store') }}" class="mt-4 grid gap-3 lg:grid-cols-[280px_1fr_auto]">
            @csrf
            <select name="student_profile_id" required class="form-control">
                <option value="">Pilih murid</option>
                @foreach ($students as $student)<option value="{{ $student->id }}" @selected((string) old('student_profile_id') === (string) $student->id)>{{ $student->user->name }} — {{ $student->user->major?->code ?? 'Tanpa jurusan' }}</option>@endforeach
            </select>
            <textarea name="content" required rows="2" placeholder="Tulis catatan murid" class="form-control">{{ old('content') }}</textarea>
            <button class="min-h-11 self-end rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white">Simpan</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="space-y-3 p-3 md:hidden" data-mobile-list="teacher-notes">
            @forelse ($notes as $note)
                <x-teacher.mobile-card>
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate font-bold text-slate-900">{{ $note->studentProfile->user->name }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ $note->studentProfile->user->major?->code ?? 'Tanpa jurusan' }}</p></div><time class="shrink-0 text-xs text-slate-500">{{ $note->created_at->format('d M Y') }}</time></div>
                    <p class="mt-3 text-sm leading-5 text-slate-700">{{ $note->content }}</p>
                    <p class="mt-2 text-xs text-slate-500">Guru: {{ $note->teacherProfile->user->name }}</p>
                    @can('update', $note)
                        <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3"><a href="{{ route('teacher.notes.edit', $note) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-amber-50 px-3 text-sm font-bold text-amber-700">Edit</a><form method="POST" action="{{ route('teacher.notes.destroy', $note) }}" class="flex-1" data-confirm="Hapus catatan untuk {{ $note->studentProfile->user->name }}?" onsubmit="return confirm(this.dataset.confirm)">@csrf @method('DELETE')<button class="min-h-11 w-full rounded-xl bg-rose-50 px-3 text-sm font-bold text-rose-700">Hapus</button></form></div>
                    @endcan
                </x-teacher.mobile-card>
            @empty
                <x-teacher.empty-state message="Belum ada catatan murid." />
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block" data-desktop-table="teacher-notes"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr>@foreach (['Murid', 'Jurusan', 'Catatan', 'Guru', 'Tanggal', 'Aksi'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($notes as $note)
                    <tr class="align-top"><td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $note->studentProfile->user->name }}</td><td class="px-4 py-3">{{ $note->studentProfile->user->major?->code ?? '—' }}</td><td class="min-w-64 px-4 py-3">{{ $note->content }}</td><td class="whitespace-nowrap px-4 py-3">{{ $note->teacherProfile->user->name }}</td><td class="whitespace-nowrap px-4 py-3">{{ $note->created_at->format('d M Y H:i') }}</td><td class="whitespace-nowrap px-4 py-3">@can('update', $note)<div class="flex gap-3"><a href="{{ route('teacher.notes.edit', $note) }}" class="font-semibold text-amber-700">Edit</a><form method="POST" action="{{ route('teacher.notes.destroy', $note) }}" data-confirm="Hapus catatan untuk {{ $note->studentProfile->user->name }}?" onsubmit="return confirm(this.dataset.confirm)">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Hapus</button></form></div>@else<span class="text-slate-400">—</span>@endcan</td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada catatan murid.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($notes->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $notes->links() }}</div>@endif
    </section>
</x-teacher-layout>
