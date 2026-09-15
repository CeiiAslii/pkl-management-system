<x-admin-layout title="Detail Staf">
    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xl font-bold text-slate-900">{{ $teacher->name }}</p><p class="mt-1 text-slate-500">{{ $teacher->email }}</p></div><a href="{{ route('admin.teachers.edit', $teacher) }}" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white">Edit</a></div>
        <dl class="mt-7 grid gap-5 sm:grid-cols-2"><div><dt class="text-sm text-slate-500">Status</dt><dd class="font-semibold">{{ $teacher->status->value === 'active' ? 'Aktif' : 'Nonaktif' }}</dd></div><div><dt class="text-sm text-slate-500">Role</dt><dd class="font-semibold">{{ $teacher->role->value === 'admin' ? 'Admin' : 'Guru' }}</dd></div><div><dt class="text-sm text-slate-500">Dibuat</dt><dd class="font-semibold">{{ $teacher->created_at->format('d M Y H:i') }}</dd></div></dl>
    </div>
</x-admin-layout>
