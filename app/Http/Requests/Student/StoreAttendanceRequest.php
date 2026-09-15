<?php

namespace App\Http\Requests\Student;

use App\Models\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Attendance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:10000'],
            'selfie' => [
                'required',
                'string',
                'max:3000000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! preg_match('/^data:image\/(jpeg|png|webp);base64,/', $value)) {
                        $fail('Foto selfie harus berupa gambar kamera yang valid.');

                        return;
                    }

                    $binary = base64_decode(substr($value, strpos($value, ',') + 1), true);
                    $mime = $binary === false ? false : (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);

                    if ($binary === false || strlen($binary) > 2 * 1024 * 1024 || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $fail('Foto selfie harus berupa JPG, JPEG, PNG, atau WEBP dengan ukuran maksimal 2 MB.');
                    }
                },
            ],
            'student_id' => ['missing'],
            'student_profile_id' => ['missing'],
            'user_id' => ['missing'],
            'attendance_date' => ['missing'],
            'check_in_at' => ['missing'],
            'check_out_at' => ['missing'],
            'status' => ['missing'],
            'chat_id' => ['missing'],
            'thread_id' => ['missing'],
            'message_thread_id' => ['missing'],
            'telegram_destination' => ['missing'],
            'major' => ['missing'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Izin lokasi diperlukan untuk melakukan absensi.',
            'longitude.required' => 'Izin lokasi diperlukan untuk melakukan absensi.',
            'accuracy.required' => 'Izin lokasi diperlukan untuk melakukan absensi.',
            'selfie.required' => 'Izin kamera dan foto selfie diperlukan untuk melakukan absensi.',
        ];
    }
}
