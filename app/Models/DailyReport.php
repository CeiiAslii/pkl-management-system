<?php

namespace App\Models;

use Database\Factories\DailyReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'report_date',
    'activity_description',
    'activity_photo_path',
    'activity_photo_original_path',
    'had_photo',
    'photo_delivery_token',
    'photo_telegram_sent_at',
])]
class DailyReport extends Model
{
    /** @use HasFactory<DailyReportFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'had_photo' => 'boolean',
            'photo_telegram_sent_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function hasAvailableActivityPhoto(): bool
    {
        return $this->activity_photo_path !== null
            && Storage::disk('local')->exists($this->activity_photo_path);
    }
}
