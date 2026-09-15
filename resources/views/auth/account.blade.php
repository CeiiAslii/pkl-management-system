@extends('layouts.auth')
@section('title', 'Account')
@section('content')
    <h1>Account</h1>
    <p>Signed in as {{ $user->name }}.</p>
    <p>Role: {{ $user->role->value }}. Status: {{ $user->status->value }}.</p>
    @if ($user->studentProfile)
        <p><a href="{{ route('student-profiles.show', $user->studentProfile) }}">My student profile</a></p>
    @endif
    @if ($user->teacherProfile)
        <p><a href="{{ route('teacher-profiles.show', $user->teacherProfile) }}">My teacher profile</a></p>
    @endif
    @can('viewAny', App\Models\User::class)
        <a href="{{ route('student-approvals.index') }}">Review student registrations</a>
    @endcan
@endsection
