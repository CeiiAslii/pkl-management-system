<?php

namespace App\Http\Controllers;

use App\Exports\StudentRecapExport;
use App\Http\Requests\ExportStudentRecapRequest;
use App\Models\StudentProfile;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentRecapExportController extends Controller
{
    public function __invoke(ExportStudentRecapRequest $request, StudentProfile $studentProfile): BinaryFileResponse
    {
        $validated = $request->validated();
        $export = new StudentRecapExport(
            $studentProfile,
            isset($validated['date_from']) ? CarbonImmutable::parse($validated['date_from'])->startOfDay() : null,
            isset($validated['date_to']) ? CarbonImmutable::parse($validated['date_to'])->endOfDay() : null,
        );
        $path = $export->generate();

        return response()->download(
            $path,
            $export->filename(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
