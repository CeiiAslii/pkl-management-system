<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ActiveStudentScope
{
    /** @return Builder<StudentProfile> */
    public function query(): Builder
    {
        return StudentProfile::query()->whereHas('user', fn (Builder $query) => $query
            ->where('role', Role::Student)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('approved_by'));
    }

    public function teacherProfile(User $teacher): TeacherProfile
    {
        $teacherProfile = $teacher->teacherProfile;
        abort_if($teacherProfile === null, 403, 'Profil guru belum tersedia.');

        return $teacherProfile;
    }
}
