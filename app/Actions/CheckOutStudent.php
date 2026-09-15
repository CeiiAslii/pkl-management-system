<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CheckOutStudent
{
    public function __construct(private StoreAttendanceSelfie $storeSelfie) {}

    /** @param array{latitude: numeric-string|float|int, longitude: numeric-string|float|int, accuracy: numeric-string|float|int, selfie: string} $data */
    public function handle(User $student, array $data): Attendance
    {
        $selfiePath = null;

        try {
            return DB::transaction(function () use ($student, $data, &$selfiePath): Attendance {
                $profile = StudentProfile::query()
                    ->where('user_id', $student->id)
                    ->firstOrFail();
                $attendance = $profile->attendances()
                    ->whereDate('attendance_date', today())
                    ->lockForUpdate()
                    ->first();

                if ($attendance === null) {
                    throw ValidationException::withMessages(['attendance' => 'Anda harus melakukan absen masuk terlebih dahulu.']);
                }

                Gate::forUser($student)->authorize('update', $attendance);

                if ($attendance->check_out_at !== null) {
                    throw ValidationException::withMessages(['attendance' => 'Anda sudah melakukan absen pulang hari ini.']);
                }

                $latitude = (float) $data['latitude'];
                $longitude = (float) $data['longitude'];
                $selfiePath = $this->storeSelfie->handle($data['selfie'], $profile->id);
                $attendance->update([
                    'check_out_at' => now(),
                    'check_out_latitude' => $latitude,
                    'check_out_longitude' => $longitude,
                    'check_out_accuracy' => (float) $data['accuracy'],
                    'check_out_selfie_path' => $selfiePath,
                ]);

                return $attendance;
            }, 3);
        } catch (\Throwable $exception) {
            if ($selfiePath !== null) {
                Storage::disk('local')->delete($selfiePath);
            }

            throw $exception;
        }
    }
}
