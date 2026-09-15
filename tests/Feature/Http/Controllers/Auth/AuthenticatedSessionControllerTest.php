<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_uses_name_or_email_identifier(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('E-PKL')
            ->assertSee('Sistem Manajemen Praktik Kerja Lapangan')
            ->assertDontSee('<img', false)
            ->assertSee('Nama atau Email')
            ->assertSee('form-control mt-2', false)
            ->assertSee('min-h-11', false)
            ->assertDontSee('Email atau Username');
    }

    public function test_root_route_sends_guests_and_active_users_to_their_destinations(): void
    {
        $this->get(route('home'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->admin()->create())->get(route('home'))->assertRedirectToRoute('admin.dashboard');
        $this->actingAs(User::factory()->teacher()->create())->get(route('home'))->assertRedirectToRoute('teacher.dashboard');
        $this->actingAs(User::factory()->approvedStudent()->create())->get(route('home'))->assertRedirectToRoute('student.dashboard');
    }

    #[TestWith(['admin'])]
    #[TestWith(['teacher'])]
    #[TestWith(['approvedStudent'])]
    public function test_active_accounts_can_sign_in_with_email_case_insensitively(string $state): void
    {
        $user = User::factory()->{$state}()->create();
        $this->get(route('login'));
        $sessionId = session()->getId();

        $this->post(route('login'), ['identifier' => mb_strtoupper($user->email), 'password' => 'password'])
            ->assertRedirectToRoute('account');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_user_can_sign_in_with_full_name_while_preserving_spaces(): void
    {
        $user = User::factory()->teacher()->create(['name' => 'Guru Demo A']);

        $this->post(route('login'), ['identifier' => 'Guru Demo A', 'password' => 'password'])
            ->assertRedirectToRoute('account');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Guru Demo A', $user->fresh()->name);
    }

    public function test_name_login_is_case_insensitive_and_normalizes_repeated_surrounding_spaces(): void
    {
        $user = User::factory()->approvedStudent()->create(['name' => 'Guru   Demo A']);

        $this->post(route('login'), [
            'identifier' => '  gUrU   dEmO a  ',
            'password' => 'password',
        ])->assertRedirectToRoute('account');

        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_full_names_are_rejected_as_ambiguous_but_each_email_still_works(): void
    {
        $first = User::factory()->teacher()->create(['name' => 'Nama Yang Sama', 'email' => 'first@example.test']);
        User::factory()->teacher()->create(['name' => 'nama yang sama', 'email' => 'second@example.test']);

        $this->postJson(route('login'), ['identifier' => ' NAMA  YANG SAMA ', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.identifier.0', 'Nama tersebut digunakan oleh lebih dari satu akun. Silakan masuk menggunakan email.');
        $this->assertGuest();

        $this->post(route('login'), ['identifier' => $first->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->assertAuthenticatedAs($first);
    }

    public function test_pending_student_cannot_sign_in_by_name_or_email(): void
    {
        $student = User::factory()->create(['name' => 'Murid Pending']);

        foreach ([$student->name, $student->email] as $identifier) {
            $this->postJson(route('login'), ['identifier' => $identifier, 'password' => 'password'])
                ->assertUnprocessable()->assertJsonValidationErrors('identifier');
            $this->assertGuest();
        }
    }

    public function test_active_status_without_approval_does_not_grant_student_access(): void
    {
        $student = User::factory()->create(['status' => AccountStatus::Active]);

        $this->postJson(route('login'), ['identifier' => $student->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('identifier');

        $this->assertGuest();
        $this->actingAs($student)->get(route('account'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_suspended_account_cannot_sign_in_by_name_or_email(): void
    {
        $user = User::factory()->teacher()->suspended()->create(['name' => 'Guru Nonaktif']);

        foreach ([$user->name, $user->email] as $identifier) {
            $this->postJson(route('login'), ['identifier' => $identifier, 'password' => 'password'])
                ->assertUnprocessable()->assertJsonValidationErrors('identifier');
            $this->assertGuest();
        }
    }

    public function test_wrong_password_and_unknown_name_use_the_same_generic_error(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Admin Demo']);

        $response = $this->postJson(route('login'), ['identifier' => $user->name, 'password' => 'wrong-password']);
        $response->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $unknown = $this->postJson(route('login'), ['identifier' => 'Tidak Dikenal', 'password' => 'password']);
        $unknown->assertUnprocessable();

        $this->assertSame($response->json('errors.identifier'), $unknown->json('errors.identifier'));
        $this->assertGuest();
    }

    public function test_login_validates_credentials(): void
    {
        $this->postJson(route('login'), ['identifier' => ['bad'], 'password' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_login_rate_limit_cannot_be_bypassed_by_name_case_or_spacing(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('login'), ['identifier' => 'Guru Demo A', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $response = $this->postJson(route('login'), ['identifier' => '  GURU   DEMO A ', 'password' => 'wrong']);
        $seconds = (int) $response->headers->get('Retry-After');

        $response->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak percobaan masuk. Untuk keamanan akun, coba lagi dalam '.$seconds.' detik.');
        $this->assertGuest();
    }

    public function test_browser_login_throttle_renders_the_styled_login_page_and_preserves_identifier(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login'), ['identifier' => 'Tidak Ada Browser', 'password' => 'wrong-password'])
                ->assertRedirectToRoute('login');
        }

        $response = $this->post(route('login'), ['identifier' => 'Tidak Ada Browser', 'password' => 'wrong-password']);
        $seconds = (int) $response->headers->get('Retry-After');

        $response->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertSee('E-PKL')
            ->assertSee('Nama atau Email')
            ->assertSee('⚠ Terlalu banyak percobaan masuk.')
            ->assertSee('Coba lagi dalam')
            ->assertSee('Tidak Ada Browser')
            ->assertSee((string) $seconds)
            ->assertDontSee('wrong-password')
            ->assertSee('disabled', false);
    }

    public function test_get_login_does_not_consume_attempts(): void
    {
        $this->get(route('login'))->assertOk();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('login'), ['identifier' => 'Tidak Ada', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson(route('login'), ['identifier' => 'Tidak Ada', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_login_uses_configured_identifier_limit_and_cooldown(): void
    {
        config(['auth.rate_limits.login.identifier_attempts' => 2, 'auth.rate_limits.login.decay_seconds' => 90]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson(route('login'), ['identifier' => 'Configured Student', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson(route('login'), ['identifier' => 'Configured Student', 'password' => 'wrong'])
            ->assertTooManyRequests();

        $this->travel(61)->seconds();
        $this->postJson(route('login'), ['identifier' => 'Configured Student', 'password' => 'wrong'])
            ->assertTooManyRequests();

        $this->travel(30)->seconds();
        $this->postJson(route('login'), ['identifier' => 'Configured Student', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_login_uses_configured_shared_ip_abuse_limit(): void
    {
        config(['auth.rate_limits.login.ip_attempts' => 2]);

        foreach (['First Student', 'Second Student'] as $identifier) {
            $this->postJson(route('login'), ['identifier' => $identifier, 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson(route('login'), ['identifier' => 'Third Student', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_successful_login_clears_only_that_identifier_limiter(): void
    {
        $user = User::factory()->teacher()->create(['email' => 'clear-me@example.test']);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->postJson(route('login'), ['identifier' => $user->email, 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->post(route('login'), ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->post(route('logout'))->assertRedirectToRoute('login');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('login'), ['identifier' => $user->email, 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson(route('login'), ['identifier' => $user->email, 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_one_throttled_identifier_does_not_block_another_identifier_on_shared_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('login'), ['identifier' => 'Murid A', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson(route('login'), ['identifier' => 'Murid A', 'password' => 'wrong'])
            ->assertTooManyRequests();
        $this->postJson(route('login'), ['identifier' => 'Murid B', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_suspension_invalidates_an_existing_session_on_the_next_request(): void
    {
        $user = User::factory()->approvedStudent()->create();
        $this->post(route('login'), ['identifier' => $user->email, 'password' => 'password'])->assertRedirectToRoute('account');
        $user->status = AccountStatus::Suspended;
        $user->save();
        $this->app['auth']->forgetGuards();

        $this->get(route('account'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $this->actingAs(User::factory()->admin()->create())->withSession(['private_value' => 'secret']);
        $this->get(route('account'));
        $token = session()->token();

        $this->post(route('logout'))->assertRedirectToRoute('login')->assertSessionMissing('private_value');

        $this->assertGuest();
        $this->assertNotSame($token, session()->token());
        $this->get(route('account'))->assertRedirectToRoute('login');
    }
}
