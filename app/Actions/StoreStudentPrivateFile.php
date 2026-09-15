<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class StoreStudentPrivateFile
{
    public function handle(UploadedFile $file, string $directory): string
    {
        $path = $file->storeAs($directory, Str::uuid().'.'.$file->extension(), 'local');

        if ($path === false) {
            throw new RuntimeException('Berkas privat tidak dapat disimpan.');
        }

        return $path;
    }
}
