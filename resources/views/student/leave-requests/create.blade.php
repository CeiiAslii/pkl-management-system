<x-student-layout title="Buat Pengajuan">
    <section class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <p class="text-sm leading-6 text-slate-500">Pengajuan akan berstatus menunggu sampai ditinjau oleh pihak sekolah.</p>
        <form method="POST" action="{{ route('student.leave-requests.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <label for="requested_for" class="block text-sm font-semibold text-slate-700">Tanggal<input id="requested_for" name="requested_for" type="date" value="{{ old('requested_for', now()->toDateString()) }}" required class="mt-2 w-full rounded-lg border-slate-300 font-normal focus:border-sky-500 focus:ring-sky-500"></label>
                <label for="type" class="block text-sm font-semibold text-slate-700">Jenis pengajuan<select id="type" name="type" required class="mt-2 w-full rounded-lg border-slate-300 font-normal focus:border-sky-500 focus:ring-sky-500"><option value="">Pilih jenis</option><option value="izin" @selected(old('type') === 'izin')>Izin</option><option value="sakit" @selected(old('type') === 'sakit')>Sakit</option></select></label>
            </div>
            <label for="reason" class="block text-sm font-semibold text-slate-700">Alasan<textarea id="reason" name="reason" rows="6" required maxlength="2000" class="mt-2 w-full rounded-lg border-slate-300 font-normal focus:border-sky-500 focus:ring-sky-500">{{ old('reason') }}</textarea></label>
            <label for="supporting_file" class="block text-sm font-semibold text-slate-700">Bukti pendukung <span class="font-normal text-slate-500">(opsional)</span><span class="mt-1 block font-normal text-slate-500">JPG, PNG, WebP, atau PDF. Maksimal 5 MB.</span><input id="supporting_file" name="supporting_file" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-2 file:font-semibold file:text-sky-700 hover:file:bg-sky-100"></label>
            <div class="flex flex-wrap gap-3"><button type="submit" class="rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-800">Kirim pengajuan</button><a href="{{ route('student.leave-requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a></div>
        </form>
    </section>
</x-student-layout>
