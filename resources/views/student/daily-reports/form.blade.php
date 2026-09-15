<x-student-layout :title="$dailyReport->exists ? 'Ubah Laporan Harian' : 'Tambah Laporan Harian'">
    <section class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 bg-sky-50/60 p-5 sm:p-7"><p class="text-xs font-bold tracking-widest text-sky-700">LAPORAN HARIAN</p><h1 class="mt-2 text-xl font-bold text-slate-900">{{ $dailyReport->exists ? 'Perbarui kegiatan Anda' : 'Catat kegiatan hari ini' }}</h1><p class="mt-1 text-sm text-slate-500">Satu laporan untuk setiap tanggal kegiatan.</p></header>
        <form method="POST" action="{{ $dailyReport->exists ? route('student.daily-reports.update', $dailyReport) : route('student.daily-reports.store') }}" enctype="multipart/form-data" class="space-y-6 p-5 sm:p-7">
            @csrf
            @if ($dailyReport->exists) @method('PATCH') @endif
            <x-form.input label="Tanggal kegiatan" name="report_date" type="date" :value="$dailyReport->report_date?->toDateString() ?? today()->toDateString()" required />
            <div><label for="activity_description" class="block text-sm font-semibold text-slate-800">Uraian kegiatan</label><p id="activityHelp" class="mt-1 text-sm text-slate-500">Ceritakan pekerjaan atau kegiatan yang dilakukan selama PKL.</p><textarea id="activity_description" name="activity_description" rows="9" required maxlength="5000" aria-describedby="activityHelp" placeholder="Contoh: Melakukan instalasi kabel LAN, konfigurasi router, dan pengecekan koneksi." class="mt-3 w-full rounded-xl border border-slate-300 bg-white p-4 text-base leading-7 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">{{ old('activity_description', $dailyReport->activity_description) }}</textarea><p class="mt-1 text-right text-xs text-slate-500">Maksimal 5.000 karakter.</p></div>
            <section data-report-photo class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-semibold">Foto kegiatan <span class="font-normal text-slate-500">(opsional)</span></h2>
                @if ($dailyReport->activity_photo_path)
                    <div class="mt-3"><img src="{{ route('student.daily-reports.photo', $dailyReport) }}" alt="Foto kegiatan tersimpan" class="max-h-56 w-full rounded-xl object-contain"><label class="mt-3 flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo')) class="h-4 w-4 rounded border-slate-300 accent-rose-600">Hapus foto tersimpan</label></div>
                @endif
                <label for="activity_photo" class="mt-3 flex min-h-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-sky-300 bg-white px-4 py-5 text-center focus-within:ring-2 focus-within:ring-sky-500"><span class="text-sm font-semibold text-sky-700">{{ $dailyReport->activity_photo_path ? 'Pilih foto pengganti' : 'Pilih foto kegiatan' }}</span><span class="text-xs text-slate-500">JPG, PNG, atau WebP · Maksimal 5 MB</span><input id="activity_photo" name="activity_photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"></label>
                <img data-photo-preview alt="Pratinjau foto yang dipilih" class="mt-3 max-h-64 w-full rounded-xl object-contain" hidden>
                <p data-photo-status class="mt-2 text-sm text-slate-500" role="status"></p>
                <button data-photo-cancel type="button" class="mt-2 min-h-11 text-sm font-semibold text-sky-700" hidden>Batalkan pilihan foto</button>
            </section>
            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row"><button type="submit" class="min-h-12 rounded-xl bg-sky-700 px-5 py-3 text-sm font-bold text-white hover:bg-sky-800">{{ $dailyReport->exists ? 'Simpan perubahan' : 'Simpan laporan' }}</button><a href="{{ route('student.daily-reports.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700">Batal</a></div>
        </form>
    </section>
</x-student-layout>
