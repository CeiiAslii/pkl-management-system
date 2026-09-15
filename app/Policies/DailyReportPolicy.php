<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DailyReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasStudentProfile($user) || ($user->isActive() && in_array($user->role, [Role::Teacher, Role::Admin], true));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DailyReport $dailyReport): Response
    {
        return ($this->owns($user, $dailyReport) || $user->isAdmin() || $this->teacherCanView($user, $dailyReport))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasStudentProfile($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DailyReport $dailyReport): Response
    {
        return $this->owns($user, $dailyReport) ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DailyReport $dailyReport): Response
    {
        return $this->owns($user, $dailyReport) ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DailyReport $dailyReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DailyReport $dailyReport): bool
    {
        return false;
    }

    private function hasStudentProfile(User $user): bool
    {
        return $user->isActive()
            && $user->role === Role::Student
            && $user->studentProfile()->exists();
    }

    private function owns(User $user, DailyReport $dailyReport): bool
    {
        return $this->hasStudentProfile($user) && $dailyReport->studentProfile()->where('user_id', $user->id)->exists();
    }

    private function teacherCanView(User $user, DailyReport $dailyReport): bool
    {
        return $user->isActive()
            && $user->role === Role::Teacher
            && $dailyReport->studentProfile->user()->where('role', Role::Student)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('approved_by')
                ->exists();
    }
}
