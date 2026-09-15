<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExportStudentRecapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::forUser($this->user())->authorize('export', $this->route('studentProfile'));

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date_from.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'date_to.date' => 'Tanggal selesai harus berupa tanggal yang valid.',
            'date_to.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ];
    }
}
