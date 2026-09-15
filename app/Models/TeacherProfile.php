<?php

namespace App\Models;

use Database\Factories\TeacherProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_number', 'phone'])]
class TeacherProfile extends Model
{
    /** @use HasFactory<TeacherProfileFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TeacherNote::class);
    }
}
