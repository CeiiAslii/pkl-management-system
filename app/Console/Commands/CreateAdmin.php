<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'pkl:create-admin {--name=} {--email=}';

    protected $description = 'Create an active administrator using a hidden password prompt';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter the password securely.');

            return self::FAILURE;
        }

        $name = LoginIdentifier::normalize($this->option('name') ?? $this->ask('Name') ?? '');
        $email = mb_strtolower(trim($this->option('email') ?? $this->ask('Email') ?? ''));
        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make([
            'name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'confirmed', 'max:72', Password::defaults()],
        ], [
            'password.min' => 'Kata sandi minimal 12 karakter.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        if (strlen($password) > 72) {
            $this->error('The password must not exceed 72 bytes.');

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = Role::Admin;
        $user->is_protected_admin = true;
        $user->status = AccountStatus::Active;

        try {
            $user->save();
        } catch (UniqueConstraintViolationException) {
            $this->error('This email has already been registered.');

            return self::FAILURE;
        }

        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
