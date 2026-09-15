<?php

namespace App\Http\Requests\Auth;

use App\Support\LoginIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => LoginIdentifier::normalize($this->input('name'))]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'major_id' => ['required', 'integer', Rule::exists('majors', 'id')],
            'password' => ['required', 'string', 'max:72', Password::defaults()],
            'username' => ['missing'],
            'role' => ['missing'],
            'status' => ['missing'],
            'user_id' => ['missing'],
            'student_id' => ['missing'],
            'student_profile_id' => ['missing'],
            'school_class_id' => ['missing'],
            'pkl_place_id' => ['missing'],
            'academic_year_id' => ['missing'],
            'approved_at' => ['missing'],
            'approved_by' => ['missing'],
            'email_verified_at' => ['missing'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (is_string($this->input('password')) && strlen($this->input('password')) > 72) {
                $validator->errors()->add('password', 'The password must not exceed 72 bytes.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.min' => 'Kata sandi minimal 12 karakter.',
        ];
    }
}
