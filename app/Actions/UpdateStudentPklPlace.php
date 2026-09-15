<?php

namespace App\Actions;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateStudentPklPlace
{
    /** @param array{pkl_place_name?: string|null, pkl_contact_name?: string|null, pkl_phone?: string|null} $data */
    public function handle(User $student, StudentProfile $studentProfile, array $data): void
    {
        abort_unless($student->is($studentProfile->user), 403);

        $pklPlaceData = Arr::only($data, ['pkl_place_name', 'pkl_contact_name']);

        if (array_key_exists('pkl_phone', $data)) {
            $pklPlaceData['pkl_contact_phone'] = $data['pkl_phone'];
        }

        $studentProfile->update($pklPlaceData);
    }
}
