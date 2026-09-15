<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateStudentByAdmin
{
    /** @param array{name: string, email: string, major_id: int, phone?: string|null, pkl_place_name?: string|null, pkl_contact_name?: string|null, pkl_phone?: string|null, status: string} $data */
    public function handle(User $admin, User $student, array $data, UpdateStudentPklPlace $updateStudentPklPlace): void
    {
        Gate::forUser($admin)->authorize('update', $student);

        DB::transaction(function () use ($admin, $student, $data, $updateStudentPklPlace): void {
            $student = User::query()->lockForUpdate()->findOrFail($student->id);
            $student->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'major_id' => $data['major_id'],
            ]);

            $studentProfile = $student->studentProfile()->lockForUpdate()->first()
                ?? $student->studentProfile()->create();
            $studentProfile->update([
                'phone' => $data['phone'] ?? null,
            ]);
            $updateStudentPklPlace->handle($student, $studentProfile, $data);

            $this->updateStatus($admin, $student, $data['status']);
        });
    }

    private function updateStatus(User $admin, User $student, string $status): void
    {
        if ($status === AccountStatus::Active->value) {
            $student->status = AccountStatus::Active;
            $student->approved_by = $admin->id;
            $student->approved_at = now();
        } elseif ($status === AccountStatus::Suspended->value) {
            $student->status = AccountStatus::Suspended;
            $student->approved_by = null;
            $student->approved_at = null;
        } else {
            $student->status = AccountStatus::Pending;
            $student->approved_by = null;
            $student->approved_at = null;
        }

        $student->save();
    }
}
