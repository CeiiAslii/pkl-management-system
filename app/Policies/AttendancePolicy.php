<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attendance $attendance): Response
    {
        $ownsAttendance = $user->role === Role::Student
            && $attendance->studentProfile()->where('user_id', $user->id)->exists();
        $teacherCanView = $user->role === Role::Teacher
            && $attendance->studentProfile->user()->where('role', Role::Student)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('approved_by')
                ->exists();

        return $user->isActive() && ($ownsAttendance || $user->isAdmin() || $teacherCanView)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->role === Role::Student && $user->studentProfile()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->isActive()
            && $user->role === Role::Student
            && $attendance->studentProfile()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return false;
    }
}
