<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateDailyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('dailyReport'));
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
            'remove_photo' => ['nullable', 'boolean'],
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
            if ($this->boolean('remove_photo') && $this->hasFile('activity_photo')) {
                $validator->errors()->add('activity_photo', 'Pilih mengganti atau menghapus foto, bukan keduanya.');
            }

            if ($validator->errors()->has('report_date')) {
                return;
            }

            $dailyReport = $this->route('dailyReport');

            if ($this->user()->studentProfile->dailyReports()
                ->whereDate('report_date', $this->date('report_date'))
                ->whereKeyNot($dailyReport)
                ->exists()) {
                $validator->errors()->add('report_date', 'Laporan harian untuk tanggal ini sudah ada.');
            }
        }];
    }
}
