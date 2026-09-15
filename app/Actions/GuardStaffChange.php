<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GuardStaffChange
{
    /** Must run inside the caller's transaction; admin locks serialize demotion/deletion. */
    public function handle(User $actor, User $target, string $ability, Role $newRole, AccountStatus $newStatus, bool $deleting = false): User
    {
        User::query()->where('role', Role::Admin)->orderBy('id')->lockForUpdate()->get();
        $actor = User::query()->findOrFail($actor->id);
        $target = User::query()->lockForUpdate()->findOrFail($target->id);
        Gate::forUser($actor)->authorize($ability, $target);

        if ($target->role === Role::Admin && ($deleting || $newRole !== Role::Admin || $newStatus !== AccountStatus::Active)
            && ! User::query()->where('role', Role::Admin)->where('status', AccountStatus::Active)->whereKeyNot($target)->exists()) {
            throw ValidationException::withMessages(['role' => 'Minimal satu admin aktif harus tetap tersedia.']);
        }

        return $target;
    }
}
