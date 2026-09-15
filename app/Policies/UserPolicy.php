<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, User $student): bool
    {
        return $user->isAdmin()
            && $student->role === Role::Student
            && $student->status === AccountStatus::Pending;
    }

    public function reject(User $user, User $student): bool
    {
        return $this->approve($user, $student);
    }

    public function view(User $user, User $student): bool
    {
        return $user->isAdmin() && $student->role === Role::Student;
    }

    public function update(User $user, User $student): bool
    {
        return $this->view($user, $student);
    }

    public function delete(User $user, User $student): bool
    {
        return $this->view($user, $student);
    }

    public function createTeacher(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewTeacher(User $user, User $teacher): bool
    {
        return $user->isAdmin() && in_array($teacher->role, [Role::Teacher, Role::Admin], true) && ! $teacher->is_protected_admin;
    }

    public function updateTeacher(User $user, User $teacher): bool
    {
        return $this->viewTeacher($user, $teacher);
    }

    public function deleteTeacher(User $user, User $teacher): bool
    {
        return $this->viewTeacher($user, $teacher);
    }
}
