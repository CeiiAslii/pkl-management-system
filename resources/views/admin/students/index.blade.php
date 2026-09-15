<x-admin-layout title="Data Murid">
    <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3" method="GET">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
        <select name="major" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">Semua jurusan</option>
            @foreach ($majors as $major)<option value="{{ $major->id }}" @selected((string) request('major') === (string) $major->id)>{{ $major->code }} — {{ $major->name }}</option>@endforeach
        </select>
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">Semua status</option><option value="pending" @selected(request('status') === 'pending')>Pending</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="suspended" @selected(request('status') === 'suspended')>Ditolak/nonaktif</option>
        </select>
    </form>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Murid</th><th class="px-5 py-4">Jurusan</th><th class="px-5 py-4">Tempat PKL</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($students as $student)<tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $student->name }}</p><p class="text-xs text-slate-500">{{ $student->email }}</p></td><td class="px-5 py-4">{{ $student->major?->code ?? '—' }}</td><td class="px-5 py-4">{{ $student->studentProfile?->pkl_place_name ?? 'Belum diisi' }} <x-pkl-map-link :profile="$student->studentProfile" /></td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($student->status->value) }}</span></td><td class="whitespace-nowrap px-5 py-4 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.students.show', $student) }}" class="font-semibold text-sky-700 hover:text-sky-900">Lihat</a><a href="{{ route('admin.students.edit', $student) }}" class="font-semibold text-slate-700 hover:text-slate-900">Edit</a><form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Hapus akun murid {{ addslashes($student->name) }}? Riwayat PKL akan disimpan.')">@csrf @method('DELETE')<button class="font-semibold text-rose-700 hover:text-rose-900">Hapus</button></form></div></td></tr>
        @empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Data murid tidak ditemukan.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="mt-5">{{ $students->links() }}</div>
</x-admin-layout>
