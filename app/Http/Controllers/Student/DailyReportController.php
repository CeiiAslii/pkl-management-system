<?php

namespace App\Http\Controllers\Student;

use App\Actions\StoreDailyReportPhoto;
use App\Actions\UpdateDailyReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreDailyReportRequest;
use App\Http\Requests\Student\UpdateDailyReportRequest;
use App\Jobs\SendDailyReportTelegramNotification;
use App\Models\DailyReport;
use App\Services\TelegramMessageFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class DailyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DailyReport::class);

        return view('student.daily-reports.index', [
            'dailyReports' => $request->user()->studentProfile->dailyReports()->latest('report_date')->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', DailyReport::class);

        return view('student.daily-reports.form', ['dailyReport' => new DailyReport]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDailyReportRequest $request, StoreDailyReportPhoto $storePhoto): RedirectResponse
    {
        $attributes = $request->safe()->only(['report_date', 'activity_description']);
        $storedPhoto = null;

        if ($request->hasFile('activity_photo')) {
            $storedPhoto = $storePhoto->handle($request->file('activity_photo'));
            $attributes = [...$attributes,
                'activity_photo_path' => $storedPhoto['web_path'],
                'activity_photo_original_path' => $storedPhoto['original_path'],
                'had_photo' => true,
                'photo_delivery_token' => $storedPhoto['delivery_token'],
            ];
        }

        try {
            $dailyReport = $request->user()->studentProfile->dailyReports()->create($attributes);
        } catch (Throwable $exception) {
            if ($storedPhoto !== null) {
                Storage::disk('local')->delete([$storedPhoto['web_path'], $storedPhoto['original_path']]);
            }

            throw $exception;
        }

        try {
            SendDailyReportTelegramNotification::dispatch($dailyReport->id, $dailyReport->photo_delivery_token);
        } catch (Throwable $exception) {
            Log::warning('Telegram daily report notification could not be queued.', [
                'daily_report_id' => $dailyReport->id,
                'exception_type' => $exception::class,
            ]);
        }

        return to_route('student.daily-reports.index')->with('status', 'Laporan harian berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(DailyReport $dailyReport): View
    {
        Gate::authorize('update', $dailyReport);

        return view('student.daily-reports.form', compact('dailyReport'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDailyReportRequest $request, DailyReport $dailyReport, UpdateDailyReport $update, TelegramMessageFormatter $formatter): RedirectResponse
    {
        $update->handle($request, $dailyReport, $formatter);

        return to_route('student.daily-reports.index')->with('status', 'Laporan harian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReport $dailyReport): RedirectResponse
    {
        Gate::authorize('delete', $dailyReport);

        Storage::disk('local')->delete(array_filter([
            $dailyReport->activity_photo_path,
            $dailyReport->activity_photo_original_path,
        ]));
        $dailyReport->delete();

        return to_route('student.daily-reports.index')->with('status', 'Laporan harian berhasil dihapus.');
    }
}
