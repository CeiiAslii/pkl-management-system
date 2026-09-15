<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_responses_include_security_headers_without_hsts_on_local_http(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'")
            ->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_only_added_for_secure_production_requests(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_authenticated_responses_are_not_cached(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertHeaderContains('Cache-Control', 'private');
    }

    public function test_private_local_disk_has_no_generic_file_serving_routes(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertNull(Route::getRoutes()->getByName('storage.local'));
        $this->assertNull(Route::getRoutes()->getByName('storage.local.upload'));
    }

    public function test_sensitive_write_and_upload_routes_are_throttled(): void
    {
        $routeNames = [
            'student.profile.update',
            'student.attendance.check-in',
            'student.attendance.check-out',
            'student.daily-reports.store',
            'student.daily-reports.update',
            'student.daily-reports.destroy',
            'student.leave-requests.store',
            'teacher.leave-requests.review',
            'teacher.notes.store',
            'teacher.notes.update',
            'teacher.notes.destroy',
            'admin.students.update',
            'admin.students.destroy',
            'admin.teachers.store',
            'admin.teachers.update',
            'admin.teachers.destroy',
            'admin.majors.store',
            'admin.majors.update',
            'admin.majors.destroy',
        ];

        foreach ($routeNames as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] tidak ditemukan.");
            $this->assertContains('throttle:sensitive', $route->gatherMiddleware(), "Route [{$routeName}] belum dibatasi.");
        }
    }

    public function test_oversized_leave_evidence_is_rejected_and_not_stored(): void
    {
        Storage::fake('local');
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($profile->user)->postJson(route('student.leave-requests.store'), [
            'requested_for' => '2026-09-14',
            'type' => 'sakit',
            'reason' => 'Memerlukan pemeriksaan kesehatan.',
            'supporting_file' => UploadedFile::fake()->create('surat.pdf', 5121, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('supporting_file');

        $this->assertDatabaseCount('leave_requests', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_registration_uses_configured_minute_and_hour_limits(): void
    {
        config(['auth.rate_limits.registration.per_minute' => 1, 'auth.rate_limits.registration.per_hour' => 2]);

        $this->postJson(route('register'), [])->assertUnprocessable();
        $this->postJson(route('register'), [])->assertTooManyRequests();

        $this->travel(61)->seconds();
        $this->postJson(route('register'), [])->assertUnprocessable();

        $this->travel(61)->seconds();
        $this->postJson(route('register'), [])->assertTooManyRequests();
    }

    public function test_sensitive_actions_use_configured_limit(): void
    {
        config(['auth.rate_limits.sensitive_per_minute' => 1]);

        $this->actingAs(User::factory()->admin()->create());
        $this->postJson(route('admin.majors.store'), [])->assertUnprocessable();
        $this->postJson(route('admin.majors.store'), [])->assertTooManyRequests();
    }
}
