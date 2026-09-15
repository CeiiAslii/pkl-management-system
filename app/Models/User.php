<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Support\LoginIdentifier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'major_id', 'password'])]
#[Hidden(['password', 'remember_token', 'normalized_name', 'is_protected_admin', 'auth_version'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $attributes = [
        'role' => Role::Student->value,
        'status' => AccountStatus::Pending->value,
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->exists
                && ($user->isDirty('role') || $user->isDirty('status') || $user->isDirty('is_protected_admin') || $user->isDirty('password'))
                && ! $user->isDirty('auth_version')) {
                $user->auth_version = ((int) $user->getRawOriginal('auth_version')) + 1;
            }

            if ($user->isDirty('name') || $user->normalized_name === null) {
                $user->normalized_name = LoginIdentifier::key($user->name);
            }
        });
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active
            && ($this->role !== Role::Student || ($this->approved_at !== null && $this->approved_by !== null));
    }

    public function isAdmin(): bool
    {
        return $this->isActive() && $this->role === Role::Admin;
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function approvedStudents(): HasMany
    {
        return $this->hasMany(self::class, 'approved_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auth_version' => 'integer',
            'is_protected_admin' => 'boolean',
            'role' => Role::class,
            'status' => AccountStatus::class,
            'approved_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
