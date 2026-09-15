@extends('layouts.auth')
@section('title', 'Student profile')
@section('content')
    <h1>{{ $studentProfile->user->name }}</h1>
    <p>Jurusan: {{ $studentProfile->user->major?->name ?? 'Belum ditentukan' }}</p>
    <p>Tempat PKL: {{ $studentProfile->pkl_place_name ?? 'Belum diisi' }} <x-pkl-map-link :profile="$studentProfile" /></p>
    <p>Phone: {{ $studentProfile->phone }}</p>
    <p>Address: {{ $studentProfile->address }}</p>
    @can('update', $studentProfile)
        <form method="POST" action="{{ route('student-profiles.update', $studentProfile) }}">
            @csrf @method('PATCH')
            <label for="phone">Phone</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $studentProfile->phone) }}" maxlength="30">
            <label for="address">Address</label><textarea id="address" name="address" maxlength="2000">{{ old('address', $studentProfile->address) }}</textarea>
            <button type="submit">Save contact details</button>
        </form>
    @endcan
@endsection
