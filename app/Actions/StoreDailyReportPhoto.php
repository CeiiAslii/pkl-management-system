<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class StoreDailyReportPhoto
{
    /** @return array{web_path: string, original_path: string, delivery_token: string} */
    public function handle(UploadedFile $photo): array
    {
        $identifier = (string) Str::uuid();
        $webPath = 'daily-report-photos/'.$identifier.'.jpg';
        $temporaryOutput = tempnam(sys_get_temp_dir(), 'pkl-report-');

        if ($temporaryOutput === false) {
            throw new RuntimeException('Foto laporan tidak dapat disimpan.');
        }

        $originalPath = null;

        try {
            $originalPath = $photo->storeAs('daily-report-originals', $identifier.'.'.$photo->extension(), 'local');
            if ($originalPath === false) {
                throw new RuntimeException('Foto laporan tidak dapat disimpan.');
            }

            $process = new Process([
                'magick',
                $photo->extension().':'.$photo->getRealPath(),
                '-auto-orient',
                '-resize',
                '1600x1600>',
                '-strip',
                '-colorspace',
                'sRGB',
                '-quality',
                '84',
                '-define',
                'jpeg:extent=500kb',
                'jpeg:'.$temporaryOutput,
            ]);
            $process->setTimeout(30);
            $process->mustRun();

            $compressedImage = file_get_contents($temporaryOutput);
            if ($compressedImage === false || ! Storage::disk('local')->put($webPath, $compressedImage)) {
                throw new RuntimeException('Foto laporan tidak dapat dikompresi.');
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_filter([$originalPath, $webPath]));

            throw $exception;
        } finally {
            @unlink($temporaryOutput);
        }

        return [
            'web_path' => $webPath,
            'original_path' => $originalPath,
            'delivery_token' => (string) Str::uuid(),
        ];
    }
}
