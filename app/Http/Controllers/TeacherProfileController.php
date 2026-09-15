<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeacherProfileController extends Controller
{
    public function show(TeacherProfile $teacherProfile): View
    {
        Gate::authorize('view', $teacherProfile);
        $teacherProfile->load('user');

        return view('profiles.teacher', compact('teacherProfile'));
    }
}
