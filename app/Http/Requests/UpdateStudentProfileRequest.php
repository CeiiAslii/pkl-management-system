<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('studentProfile'));

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'user_id' => ['missing'],
            'student_id' => ['missing'],
            'school_class_id' => ['missing'],
            'pkl_place_id' => ['missing'],
            'academic_year_id' => ['missing'],
            'major_id' => ['missing'],
            'nis' => ['missing'],
            'role' => ['missing'],
            'status' => ['missing'],
        ];
    }
}
