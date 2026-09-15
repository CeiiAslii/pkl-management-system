<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Support\LoginIdentifier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateTeacher', $this->route('teacher')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('teacher'))],
            'password' => ['nullable', 'string', 'max:72', Password::defaults()],
            'status' => ['required', Rule::in([AccountStatus::Active, AccountStatus::Suspended])],
            'username' => ['missing'],
            'is_protected_admin' => ['missing'],
            'role' => ['sometimes', 'required', Rule::in([Role::Teacher, Role::Admin])],
            'approved_by' => ['missing'],
            'approved_at' => ['missing'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? LoginIdentifier::normalize($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (is_string($this->input('password')) && strlen($this->input('password')) > 72) {
                $validator->errors()->add('password', 'Kata sandi maksimal 72 byte.');
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
