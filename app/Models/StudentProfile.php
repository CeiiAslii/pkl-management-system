<?php

namespace App\Models;

use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['phone', 'address', 'profile_photo_path', 'pkl_place_name', 'pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy', 'pkl_contact_name', 'pkl_contact_phone'])]
#[Hidden(['legacy_pkl_address'])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pkl_latitude' => 'decimal:7',
            'pkl_longitude' => 'decimal:7',
            'pkl_location_accuracy' => 'decimal:2',
        ];
    }

    public function isProfileComplete(): bool
    {
        return filled($this->phone)
            && filled($this->pkl_place_name)
            && $this->pkl_latitude !== null
            && $this->pkl_longitude !== null;
    }

    public function pklMapUrl(): ?string
    {
        if ($this->pkl_latitude === null || $this->pkl_longitude === null
            || abs((float) $this->pkl_latitude) > 90 || abs((float) $this->pkl_longitude) > 180) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->pkl_latitude.','.$this->pkl_longitude);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function teacherNotes(): HasMany
    {
        return $this->hasMany(TeacherNote::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
