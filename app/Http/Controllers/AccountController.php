<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return to_route('admin.dashboard');
        }

        if ($request->user()->role === Role::Student) {
            return to_route('student.dashboard');
        }

        if ($request->user()->role === Role::Teacher) {
            return to_route('teacher.dashboard');
        }

        return view('auth.account', [
            'user' => $request->user()->load(['studentProfile', 'teacherProfile']),
        ]);
    }
}
