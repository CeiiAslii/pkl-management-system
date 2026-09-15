<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function show(StudentProfile $studentProfile): View
    {
        Gate::authorize('view', $studentProfile);
        $studentProfile->load(['user.major']);

        return view('profiles.student', compact('studentProfile'));
    }

    public function update(UpdateStudentProfileRequest $request, StudentProfile $studentProfile): RedirectResponse
    {
        $studentProfile->update($request->safe()->only(['phone', 'address']));

        return to_route('student-profiles.show', $studentProfile)->with('status', 'Profile updated.');
    }
}
