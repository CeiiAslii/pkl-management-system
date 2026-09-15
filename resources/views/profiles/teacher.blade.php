@extends('layouts.auth')
@section('title', 'Teacher profile')
@section('content')
    <h1>{{ $teacherProfile->user->name }}</h1>
    <p>Employee number: {{ $teacherProfile->employee_number }}</p>
    <p>Phone: {{ $teacherProfile->phone }}</p>
@endsection
