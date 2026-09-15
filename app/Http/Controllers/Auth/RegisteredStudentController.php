<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredStudentController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'majors' => Major::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(RegisterStudentRequest $request, RegisterStudent $register): RedirectResponse
    {
        $register->handle($request->validated());

        return to_route('login')->with('status', 'Registration received. Your account is pending admin approval.');
    }
}
