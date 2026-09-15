<x-admin-layout title="Edit Murid">
    <form method="POST" action="{{ route('admin.students.update', $student) }}" class="max-w-4xl space-y-5">
        @csrf @method('PATCH')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-sm font-bold tracking-wide text-sky-800">AKUN SISWA</h2><div class="mt-4 grid gap-5 sm:grid-cols-2">
            <x-form.input label="Nama lengkap" name="name" :value="$student->name" maxlength="255" required />
            <x-form.input label="Email" name="email" type="email" :value="$student->email" maxlength="255" required />
            <x-form.input label="Nomor HP" name="phone" type="tel" :value="$student->studentProfile?->phone" maxlength="30" />
            <label class="block text-sm font-semibold">Jurusan<select name="major_id" required class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach ($majors as $major)<option value="{{ $major->id }}" @selected((string) old('major_id', $student->major_id) === (string) $major->id)>{{ $major->code }} — {{ $major->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-semibold">Status akun<select name="status" required class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach (['pending' => 'Menunggu persetujuan', 'active' => 'Aktif', 'suspended' => 'Ditangguhkan'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $student->status->value) === $value)>{{ $label }}</option>@endforeach</select></label>
        </div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-sm font-bold tracking-wide text-sky-800">DATA PKL</h2><div class="mt-4 grid gap-5 sm:grid-cols-2">
            <x-form.input label="Nama tempat PKL" name="pkl_place_name" :value="$student->studentProfile?->pkl_place_name" maxlength="150" />
            <div><p class="text-sm font-semibold">Lokasi tempat PKL</p>@if ($student->studentProfile?->pklMapUrl())<p class="mt-2 text-sm text-slate-600">Lokasi sudah tersimpan</p>@endif<x-pkl-map-link :profile="$student->studentProfile" /><p class="text-xs text-slate-500">Lokasi diperbarui oleh murid melalui profilnya.</p></div>
            <x-form.input label="PIC / pembimbing lapangan" name="pkl_contact_name" :value="$student->studentProfile?->pkl_contact_name" maxlength="120" />
            <x-form.input label="Nomor kontak PIC" name="pkl_phone" type="tel" :value="$student->studentProfile?->pkl_contact_phone" maxlength="30" />
        </div></section>
        <div class="flex flex-wrap gap-3"><button class="min-h-11 rounded-xl bg-sky-700 px-5 py-3 text-sm font-bold text-white">Simpan perubahan</button><a href="{{ route('admin.students.show', $student) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold">Batal</a></div>
    </form>
</x-admin-layout>
