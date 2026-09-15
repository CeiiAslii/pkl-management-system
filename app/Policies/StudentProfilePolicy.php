<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentProfilePolicy
{
    public function view(User $user, StudentProfile $studentProfile): Response
    {
        $allowed = $user->isActive() && (
            $user->isAdmin()
            || ($user->role === Role::Student && $studentProfile->user_id === $user->id)
            || ($user->role === Role::Teacher && $studentProfile->user()->where('role', Role::Student)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('approved_by')
                ->exists())
        );

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, StudentProfile $studentProfile): Response
    {
        $allowed = $user->isActive() && ($user->isAdmin()
            || ($user->role === Role::Student && $studentProfile->user_id === $user->id));

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function export(User $user, StudentProfile $studentProfile): Response
    {
        if (! $user->isActive() || ! in_array($user->role, [Role::Admin, Role::Teacher], true)) {
            return Response::deny();
        }

        $student = $studentProfile->user()->where('role', Role::Student)->first();
        if ($student === null) {
            return Response::denyAsNotFound();
        }

        if ($user->role === Role::Teacher && ! $student->isActive()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
