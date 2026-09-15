<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveStudent
{
    public function handle(User $admin, User $student): void
    {
        DB::transaction(function () use ($admin, $student): void {
            $student = User::query()->lockForUpdate()->findOrFail($student->id);
            Gate::forUser($admin)->authorize('approve', $student);

            $student->status = AccountStatus::Active;
            $student->approved_by = $admin->id;
            $student->approved_at = now();
            $student->save();
        });
    }
}
