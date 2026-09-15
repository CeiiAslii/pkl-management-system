<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TeacherProfile $teacherProfile): Response
    {
        $allowed = $user->isActive() && ($user->isAdmin()
            || ($user->role === Role::Teacher && $teacherProfile->user_id === $user->id));

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TeacherProfile $teacherProfile): bool
    {
        return $user->isAdmin();
    }
}
