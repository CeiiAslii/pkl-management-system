<?php

namespace App\Http\Requests\Student;

use App\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreDailyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', DailyReport::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'activity_description' => ['required', 'string', 'max:5000'],
            'activity_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'student_profile_id' => ['missing'],
            'student_id' => ['missing'],
            'chat_id' => ['missing'],
            'thread_id' => ['missing'],
            'message_thread_id' => ['missing'],
            'telegram_destination' => ['missing'],
            'major' => ['missing'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('report_date')) {
                return;
            }

            if ($this->user()->studentProfile->dailyReports()->whereDate('report_date', $this->date('report_date'))->exists()) {
                $validator->errors()->add('report_date', 'Laporan harian untuk tanggal ini sudah ada.');
            }
        }];
    }
}
