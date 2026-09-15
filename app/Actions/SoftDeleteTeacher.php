<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SoftDeleteTeacher
{
    public function handle(User $admin, User $teacher, GuardStaffChange $guard): void
    {
        Gate::forUser($admin)->authorize('deleteTeacher', $teacher);
        DB::transaction(function () use ($admin, $teacher, $guard): void {
            $teacher = $guard->handle($admin, $teacher, 'deleteTeacher', Role::Teacher, AccountStatus::Suspended, true);
            $teacher->delete();
        });
    }
}
