<?php

namespace App\Models;

use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requested_for', 'type', 'reason', 'supporting_file_path', 'status', 'teacher_note', 'reviewed_by', 'reviewed_at'])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requested_for' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'reviewed_by');
    }
}
