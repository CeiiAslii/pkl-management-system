<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateTeacher
{
    /** @param array{name: string, email: string, password?: string|null, status: string} $data */
    public function handle(User $admin, User $teacher, array $data, GuardStaffChange $guard): User
    {
        Gate::forUser($admin)->authorize('updateTeacher', $teacher);

        return DB::transaction(function () use ($admin, $teacher, $data, $guard): User {
            $newRole = Role::from($data['role'] ?? $teacher->role->value);
            $newStatus = AccountStatus::from($data['status']);
            $teacher = $guard->handle($admin, $teacher, 'updateTeacher', $newRole, $newStatus);
            $teacher->role = $newRole;
            $teacher->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
            if (! empty($data['password'])) {
                $teacher->password = $data['password'];
            }
            $teacher->status = AccountStatus::from($data['status']);
            $teacher->save();
            $teacher->teacherProfile()->firstOrCreate();

            return $teacher;
        });
    }
}
