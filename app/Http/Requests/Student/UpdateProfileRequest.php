<?php

namespace App\Http\Requests\Student;

use App\Support\LoginIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'pkl_place_name' => ['nullable', 'string', 'max:150', 'required_with:pkl_contact_name,pkl_phone,pkl_latitude,pkl_longitude,pkl_location_accuracy'],
            'pkl_place_address' => ['missing'],
            'legacy_pkl_address' => ['missing'],
            'pkl_contact_name' => ['nullable', 'string', 'max:120'],
            'pkl_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'pkl_latitude' => ['nullable', 'required_with:pkl_longitude,pkl_location_accuracy', 'numeric', 'between:-90,90'],
            'pkl_longitude' => ['nullable', 'required_with:pkl_latitude,pkl_location_accuracy', 'numeric', 'between:-180,180'],
            'pkl_location_accuracy' => ['nullable', 'required_with:pkl_latitude,pkl_longitude', 'numeric', 'between:0,10000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:ratio=1/1'],
            'username' => ['missing'],
            'role' => ['missing'],
            'status' => ['missing'],
            'approved_at' => ['missing'],
            'approved_by' => ['missing'],
            'nis' => ['missing'],
            'school_class_id' => ['missing'],
            'major_id' => ['missing'],
            'pkl_place_id' => ['missing'],
            'academic_year_id' => ['missing'],
            'profile_photo_path' => ['missing'],
            'student_profile_id' => ['missing'],
            'user_id' => ['missing'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pkl_place_name.required_with' => 'Nama tempat PKL wajib diisi jika data PKL lainnya diisi.',
            'pkl_latitude.required_with' => 'Ambil lokasi untuk melengkapi latitude PKL.',
            'pkl_longitude.required_with' => 'Ambil lokasi untuk melengkapi longitude PKL.',
            'pkl_location_accuracy.required_with' => 'Akurasi GPS wajib disertakan bersama lokasi PKL.',
            'pkl_latitude.numeric' => 'Latitude PKL harus berupa angka.',
            'pkl_longitude.numeric' => 'Longitude PKL harus berupa angka.',
            'pkl_location_accuracy.numeric' => 'Akurasi GPS harus berupa angka.',
            'pkl_latitude.between' => 'Latitude PKL harus antara -90 dan 90.',
            'pkl_longitude.between' => 'Longitude PKL harus antara -180 dan 180.',
            'pkl_location_accuracy.between' => 'Akurasi GPS harus antara 0 dan 10.000 meter.',
            'profile_photo.image' => 'Foto profil harus berupa gambar yang valid.',
            'profile_photo.mimes' => 'Foto profil harus berformat JPG, PNG, atau WEBP.',
            'profile_photo.max' => 'Ukuran foto profil maksimal 2 MB.',
            'profile_photo.dimensions' => 'Foto profil harus dipotong dengan rasio persegi 1:1.',
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
