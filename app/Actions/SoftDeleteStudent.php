<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SoftDeleteStudent
{
    public function handle(User $admin, User $student): void
    {
        Gate::forUser($admin)->authorize('delete', $student);

        DB::transaction(function () use ($student): void {
            User::query()->lockForUpdate()->findOrFail($student->id)->delete();
        });
    }
}
