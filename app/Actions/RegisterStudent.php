<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterStudent
{
    /** @param array{name: string, email: string, major_id: int, password: string} $data */
    public function handle(array $data): User
    {
        try {
            return DB::transaction(function () use ($data): User {
                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'major_id' => $data['major_id'],
                    'password' => $data['password'],
                ]);
                $user->role = Role::Student;
                $user->status = AccountStatus::Pending;
                $user->save();
                $user->studentProfile()->create();

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email sudah terdaftar.']);
        }
    }
}
