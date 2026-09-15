<?php

namespace App\Actions;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\ActiveStudentScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewLeaveRequest
{
    public function __construct(private ActiveStudentScope $activeStudents) {}

    /** @param array{status: string, teacher_note?: string|null} $data */
    public function handle(User $teacher, LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        Gate::forUser($teacher)->authorize('review', $leaveRequest);

        return DB::transaction(function () use ($teacher, $leaveRequest, $data): LeaveRequest {
            $leaveRequest = LeaveRequest::query()->lockForUpdate()->findOrFail($leaveRequest->id);
            abort_if($leaveRequest->status !== 'pending', 409, 'Permohonan ini sudah ditinjau.');
            $leaveRequest->update([
                'status' => $data['status'],
                'teacher_note' => $data['teacher_note'] ?? null,
                'reviewed_by' => $this->activeStudents->teacherProfile($teacher)->id,
                'reviewed_at' => now(),
            ]);

            return $leaveRequest;
        });
    }
}
