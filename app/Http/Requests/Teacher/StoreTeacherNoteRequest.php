<?php

namespace App\Http\Requests\Teacher;

use App\Models\TeacherNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TeacherNote::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'integer', Rule::exists('student_profiles', 'id')],
            'content' => ['required', 'string', 'max:5000'],
            'teacher_profile_id' => ['missing'],
        ];
    }
}
