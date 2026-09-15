<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CheckInStudent
{
    public function __construct(private StoreAttendanceSelfie $storeSelfie) {}

    /** @param array{latitude: numeric-string|float|int, longitude: numeric-string|float|int, accuracy: numeric-string|float|int, selfie: string} $data */
    public function handle(User $student, array $data): Attendance
    {
        Gate::forUser($student)->authorize('create', Attendance::class);
        $selfiePath = null;

        try {
            return DB::transaction(function () use ($student, $data, &$selfiePath): Attendance {
                $profile = StudentProfile::query()
                    ->where('user_id', $student->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($profile->attendances()->whereDate('attendance_date', today())->exists()) {
                    throw ValidationException::withMessages(['attendance' => 'Anda sudah melakukan absen masuk hari ini.']);
                }

                $latitude = (float) $data['latitude'];
                $longitude = (float) $data['longitude'];
                $selfiePath = $this->storeSelfie->handle($data['selfie'], $profile->id);

                return $profile->attendances()->create([
                    'attendance_date' => today(),
                    'check_in_at' => now(),
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_accuracy' => (float) $data['accuracy'],
                    'check_in_selfie_path' => $selfiePath,
                    'status' => 'hadir',
                    'late_status' => null,
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            if ($selfiePath !== null) {
                Storage::disk('local')->delete($selfiePath);
            }

            throw $exception;
        }
    }
}
