<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
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
    public function view(User $user, LeaveRequest $leaveRequest): Response
    {
        return ($this->owns($user, $leaveRequest) || $user->isAdmin() || $this->teacherCanView($user, $leaveRequest))
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
    public function update(User $user, LeaveRequest $leaveRequest): Response
    {
        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): Response
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->review($user, $leaveRequest)->allowed();
    }

    public function review(User $user, LeaveRequest $leaveRequest): Response
    {
        return ($user->isAdmin() || $this->teacherCanView($user, $leaveRequest))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function hasStudentProfile(User $user): bool
    {
        return $user->isActive()
            && $user->role === Role::Student
            && $user->studentProfile()->exists();
    }

    private function owns(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->hasStudentProfile($user) && $leaveRequest->studentProfile()->where('user_id', $user->id)->exists();
    }

    private function teacherCanView(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isActive()
            && $user->role === Role::Teacher
            && $leaveRequest->studentProfile->user()->where('role', Role::Student)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('approved_by')
                ->exists();
    }
}
