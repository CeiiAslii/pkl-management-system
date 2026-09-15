@props([
    'majors',
    'searchName' => 'student',
    'searchValue' => null,
    'placeholder' => 'Cari nama murid lalu Enter',
])

<form method="GET" {{ $attributes->class(['mb-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2 sm:gap-3 sm:p-4']) }}>
    {{ $slot }}
    <label class="sr-only" for="{{ $searchName }}-filter">Cari nama murid</label>
    <input id="{{ $searchName }}-filter" name="{{ $searchName }}" value="{{ $searchValue ?? request($searchName) }}" placeholder="{{ $placeholder }}" autocomplete="off" class="form-control">

    <label class="sr-only" for="major-filter">Jurusan</label>
    <select id="major-filter" name="major" onchange="this.form.submit()" class="form-control">
        <option value="">Semua jurusan</option>
        @foreach ($majors as $major)
            <option value="{{ $major->id }}" @selected((string) request('major') === (string) $major->id)>{{ $major->code }}</option>
        @endforeach
    </select>
</form>
