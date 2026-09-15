<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\TeacherNote;
use App\Policies\AttendancePolicy;
use App\Policies\DailyReportPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\SchoolDataPolicy;
use App\Policies\TeacherNotePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12));

        $tooManyAttempts = function (Request $request, array $headers) {
            $message = 'Terlalu banyak percobaan. Silakan tunggu sebentar lalu coba lagi.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 429, $headers)
                : response($message, 429, $headers);
        };

        Gate::policy(Major::class, SchoolDataPolicy::class);
        Gate::policy(DailyReport::class, DailyReportPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(TeacherNote::class, TeacherNotePolicy::class);

        RateLimiter::for('registration', fn (Request $request): array => [
            Limit::perMinute(config('auth.rate_limits.registration.per_minute'))->by('registration-minute:'.$request->ip())->response($tooManyAttempts),
            Limit::perHour(config('auth.rate_limits.registration.per_hour'))->by('registration-hour:'.$request->ip())->response($tooManyAttempts),
        ]);
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(config('auth.rate_limits.sensitive_per_minute'))
            ->by(($request->user()?->id ?? $request->ip()).'|'.($request->route()?->getName() ?? $request->path()))
            ->response($tooManyAttempts));
    }
}
