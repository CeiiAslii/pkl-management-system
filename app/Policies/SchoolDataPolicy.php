<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SchoolDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isAdmin();
    }
}
