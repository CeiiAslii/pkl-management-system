<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMajorRequest extends FormRequest
{
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
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('majors', 'code')->ignore($this->route('major'))],
            'name' => ['required', 'string', 'max:120'],
        ];
    }
}
