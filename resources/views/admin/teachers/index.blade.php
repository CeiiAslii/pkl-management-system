<x-admin-layout title="Data Guru">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-[1fr_180px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            <select name="status" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="suspended" @selected(request('status') === 'suspended')>Nonaktif</option></select>

        </form>
        <a href="{{ route('admin.teachers.create') }}" class="rounded-xl bg-sky-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-sky-700">Tambah Guru</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Guru</th><th class="px-5 py-3">Peran</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($teachers as $teacher)<tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $teacher->name }}</p><p class="text-slate-500">{{ $teacher->email }}</p></td><td class="px-5 py-4">{{ $teacher->role->value === 'admin' ? 'Admin' : 'Guru' }}</td><td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold','bg-emerald-50 text-emerald-700'=>$teacher->status->value==='active','bg-amber-50 text-amber-700'=>$teacher->status->value!=='active'])>{{ $teacher->status->value === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><a class="font-semibold text-sky-700" href="{{ route('admin.teachers.show', $teacher) }}">Lihat</a><a class="font-semibold text-amber-700" href="{{ route('admin.teachers.edit', $teacher) }}">Edit</a><form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('Hapus akun staf {{ addslashes($teacher->name) }}?')">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Hapus</button></form></div></td></tr>
        @empty <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada data guru.</td></tr>@endforelse
        </tbody></table></div>
        <div class="border-t border-slate-200 p-4">{{ $teachers->links() }}</div>
    </div>
</x-admin-layout>
