<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RejectStudent
{
    public function handle(User $admin, User $student): void
    {
        DB::transaction(function () use ($admin, $student): void {
            $student = User::query()->lockForUpdate()->findOrFail($student->id);
            Gate::forUser($admin)->authorize('reject', $student);

            $student->status = AccountStatus::Suspended;
            $student->save();
        });
    }
}
