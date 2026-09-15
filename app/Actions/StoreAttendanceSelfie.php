<?php

namespace App\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreAttendanceSelfie
{
    public function handle(string $dataUrl, int $studentProfileId): string
    {
        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        $mime = $binary === false ? false : (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($binary === false || $extension === null || strlen($binary) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['selfie' => 'Foto selfie tidak valid.']);
        }

        $path = "attendance-selfies/{$studentProfileId}/".Str::uuid().'.'.$extension;
        if (! Storage::disk('local')->put($path, $binary)) {
            throw ValidationException::withMessages(['selfie' => 'Foto selfie gagal disimpan. Silakan coba lagi.']);
        }

        return $path;
    }
}
