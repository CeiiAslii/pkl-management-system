<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateTeacher
{
    /** @param array{name: string, email: string, password: string, status?: string|null} $data */
    public function handle(User $admin, array $data): User
    {
        Gate::forUser($admin)->authorize('createTeacher', User::class);

        return DB::transaction(function () use ($data): User {
            $teacher = new User;
            $teacher->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $teacher->role = Role::Teacher;
            $teacher->status = AccountStatus::from($data['status'] ?? AccountStatus::Active->value);
            $teacher->save();
            $teacher->teacherProfile()->create();

            return $teacher;
        });
    }
}
