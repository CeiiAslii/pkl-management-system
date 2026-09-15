<x-admin-layout title="Pendaftaran Murid">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3"><p class="text-sm text-slate-500">Daftar akun murid yang menunggu persetujuan admin.</p><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">{{ $students->total() }} pending</span></div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Murid</th><th class="px-5 py-4">Profil murid</th><th class="px-5 py-4">Tanggal daftar</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $student->name }}</p><p class="text-slate-500">{{ $student->email }}</p></td>
                            <td class="px-5 py-4 text-slate-600"><span class="text-xs">{{ $student->major?->name ?? 'Jurusan belum dipilih' }}</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ $student->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><form method="POST" action="{{ route('admin.registrations.approve', $student) }}" onsubmit="return confirm('Setujui pendaftaran murid ini?')">@csrf <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">Setujui</button></form><form method="POST" action="{{ route('admin.registrations.reject', $student) }}" onsubmit="return confirm('Tolak pendaftaran murid ini?')">@csrf <button class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700">Tolak</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Tidak ada pendaftaran yang menunggu persetujuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $students->links() }}</div>
</x-admin-layout>
