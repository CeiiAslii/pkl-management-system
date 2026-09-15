<?php

namespace App\Http\Requests\Admin;

use App\Support\LoginIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student'))],
            'major_id' => ['required', 'integer', Rule::exists('majors', 'id')],
            'nis' => ['missing'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'pkl_place_name' => ['nullable', 'string', 'max:150', 'required_with:pkl_contact_name,pkl_phone'],
            'pkl_place_address' => ['missing'],
            'legacy_pkl_address' => ['missing'],
            'pkl_contact_name' => ['nullable', 'string', 'max:120'],
            'pkl_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'status' => ['required', 'in:pending,active,suspended'],
            'pkl_latitude' => ['missing'],
            'pkl_longitude' => ['missing'],
            'pkl_location_accuracy' => ['missing'],
            'role' => ['missing'],
            'password' => ['missing'],
            'password_hash' => ['missing'],
            'remember_token' => ['missing'],
            'approved_at' => ['missing'],
            'approved_by' => ['missing'],
            'pkl_place_id' => ['missing'],
            'school_class_id' => ['missing'],
            'academic_year_id' => ['missing'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'name' => is_string($this->input('name')) ? LoginIdentifier::normalize($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
            'phone' => is_string($this->input('phone')) ? trim($this->input('phone')) : $this->input('phone'),
            'pkl_place_name' => is_string($this->input('pkl_place_name')) ? trim($this->input('pkl_place_name')) : $this->input('pkl_place_name'),
            'pkl_contact_name' => is_string($this->input('pkl_contact_name')) ? trim($this->input('pkl_contact_name')) : $this->input('pkl_contact_name'),
            'pkl_phone' => is_string($this->input('pkl_phone')) ? trim($this->input('pkl_phone')) : $this->input('pkl_phone'),
        ];

        $this->merge(array_intersect_key($normalized, $this->all()));
    }
}
