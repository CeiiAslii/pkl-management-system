<?php

namespace App\Http\Requests\Student;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreLeaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', LeaveRequest::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'requested_for' => ['required', 'date'],
            'type' => ['required', 'in:izin,sakit'],
            'reason' => ['required', 'string', 'max:2000'],
            'supporting_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'student_profile_id' => ['missing'],
            'student_id' => ['missing'],
            'status' => ['missing'],
        ];
    }
}
