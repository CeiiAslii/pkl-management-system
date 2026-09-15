<x-teacher-layout title="Edit Catatan Murid">
    <form method="POST" action="{{ route('teacher.notes.update', $teacherNote) }}" class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        <p class="mb-3 font-semibold text-slate-900">{{ $teacherNote->studentProfile->user->name }}</p>
        <textarea name="content" required rows="7" class="form-control">{{ old('content', $teacherNote->content) }}</textarea>
        <div class="mt-4 grid grid-cols-2 gap-2 sm:flex sm:gap-3">
            <button class="min-h-11 rounded-xl bg-sky-600 px-5 font-semibold text-white">Simpan</button>
            <a href="{{ route('teacher.notes.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-5 font-semibold text-slate-700">Batal</a>
        </div>
    </form>
</x-teacher-layout>
