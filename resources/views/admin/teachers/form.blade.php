@php($editing = isset($teacher))
<x-admin-layout :title="$editing ? 'Edit Staf' : 'Tambah Guru'">
    <form method="POST" action="{{ $editing ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}" class="max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        @if($editing) @method('PUT') @endif
        <div><label class="mb-1.5 block text-sm font-semibold">Nama lengkap</label><input name="name" value="{{ old('name', $teacher->name ?? '') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></div>
        <div><label class="mb-1.5 block text-sm font-semibold">Email</label><input type="email" name="email" value="{{ old('email', $teacher->email ?? '') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></div>
        <div><label class="mb-1.5 block text-sm font-semibold">Kata sandi {{ $editing ? '(opsional)' : '' }}</label><input type="password" name="password" minlength="12" maxlength="72" {{ $editing ? '' : 'required' }} class="w-full rounded-xl border border-slate-300 px-4 py-3"><p class="mt-1 text-xs text-slate-500">Kata sandi minimal 12 karakter. Kosongkan saat edit jika tidak diubah.</p></div>
        @if ($editing)
            <label class="block text-sm font-semibold">Peran<select name="role" required class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-3"><option value="teacher" @selected(old('role', $teacher->role->value) === 'teacher')>Guru</option><option value="admin" @selected(old('role', $teacher->role->value) === 'admin')>Admin</option></select></label>
        @endif
        <div><label class="mb-1.5 block text-sm font-semibold">Status akun</label><select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="active" @selected(old('status', $teacher->status->value ?? 'active') === 'active')>Aktif</option><option value="suspended" @selected(old('status', $teacher->status->value ?? 'active') === 'suspended')>Nonaktif</option></select></div>
        <div class="flex gap-3"><button class="rounded-xl bg-sky-600 px-5 py-2.5 font-semibold text-white">{{ $editing ? 'Simpan perubahan' : 'Buat akun guru' }}</button><a href="{{ route('admin.teachers.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold">Batal</a></div>
    </form>
</x-admin-layout>
