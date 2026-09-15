<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\TeacherNote;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherNotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && in_array($user->role, [Role::Teacher, Role::Admin], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TeacherNote $teacherNote): Response
    {
        return ($user->isAdmin() || $this->owns($user, $teacherNote)) ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->role === Role::Teacher && $user->teacherProfile()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TeacherNote $teacherNote): Response
    {
        return $this->owns($user, $teacherNote) ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TeacherNote $teacherNote): Response
    {
        return $this->owns($user, $teacherNote) ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TeacherNote $teacherNote): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TeacherNote $teacherNote): bool
    {
        return false;
    }

    private function owns(User $user, TeacherNote $teacherNote): bool
    {
        return $user->isActive()
            && $user->role === Role::Teacher
            && $teacherNote->teacher_profile_id === $user->teacherProfile?->id;
    }
}
