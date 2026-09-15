<?php

namespace App\Http\Controllers\Student;

use App\Actions\StoreStudentPrivateFile;
use App\Actions\UpdateStudentPklPlace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $studentProfile = $request->user()->studentProfile;

        return view('student.profile', [
            'studentProfile' => $studentProfile,
        ]);
    }

    public function update(
        UpdateProfileRequest $request,
        StoreStudentPrivateFile $storePrivateFile,
        UpdateStudentPklPlace $updateStudentPklPlace,
    ): RedirectResponse {
        $user = $request->user();
        $studentProfile = $user->studentProfile()->firstOrCreate();
        $oldPhotoPath = $studentProfile->profile_photo_path;
        $newPhotoPath = null;

        try {
            if ($request->hasFile('profile_photo')) {
                $newPhotoPath = $storePrivateFile->handle($request->file('profile_photo'), 'student-profile-photos');
            }

            DB::transaction(function () use ($request, $updateStudentPklPlace, $user, $studentProfile, $newPhotoPath): void {
                $user->update($request->safe()->only(['name', 'email']));
                $profileData = $request->safe()->only(['phone']);

                if ($newPhotoPath !== null) {
                    $profileData['profile_photo_path'] = $newPhotoPath;
                }

                $locationData = $request->safe()->only(['pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy']);
                if (isset($locationData['pkl_latitude'], $locationData['pkl_longitude'], $locationData['pkl_location_accuracy'])) {
                    $profileData = [...$profileData, ...$locationData];
                }

                $studentProfile->update($profileData);
                $updateStudentPklPlace->handle($user, $studentProfile, $request->safe()->only(['pkl_place_name', 'pkl_contact_name', 'pkl_phone']));
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath !== null) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath !== null && $oldPhotoPath !== null && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return to_route('student.profile.show')->with('status', 'Profil berhasil diperbarui.');
    }
}
