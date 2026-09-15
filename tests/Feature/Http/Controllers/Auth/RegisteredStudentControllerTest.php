<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegisteredStudentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_form_exposes_name_email_major_and_password_only(): void
    {
        $major = Major::factory()->create(['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan']);

        $this->get(route('register'))->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="major_id"', false)
            ->assertSee($major->code)
            ->assertSee('name="password"', false)
            ->assertSee('minlength="12"', false)
            ->assertSee('Kata sandi minimal 12 karakter.')
            ->assertSee('form-control mt-2', false)
            ->assertSee('Daftar sebagai murid')
            ->assertSee('Sudah punya akun?')
            ->assertDontSee('name="username"', false)
            ->assertDontSee('password_confirmation', false)
            ->assertDontSee('Gunakan huruf kecil', false)
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_registration_creates_a_pending_student_without_username_or_logging_in(): void
    {
        $data = [...$this->registrationData(), 'name' => 'Murid Demo A', 'email' => 'STUDENT@EXAMPLE.COM'];

        $this->post(route('register'), $data)->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Registration received. Your account is pending admin approval.');

        $user = User::query()->where('email', 'student@example.com')->firstOrFail();
        $this->assertNull($user->username);
        $this->assertSame('Murid Demo A', $user->name);
        $this->assertSame(Role::Student, $user->role);
        $this->assertSame($data['major_id'], $user->major_id);
        $this->assertDatabaseHas('student_profiles', ['user_id' => $user->id]);
        $this->assertSame($data['major_id'], $user->studentProfile->user->major->id);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertNull($user->approved_at);
        $this->assertNull($user->approved_by);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check($data['password'], $user->password));
        $this->assertGuest();
    }

    public function test_registration_allows_duplicate_names_because_email_remains_the_unique_identity(): void
    {
        User::factory()->create(['name' => 'Murid Demo A']);

        $this->post(route('register'), [...$this->registrationData(), 'name' => 'Murid Demo A'])
            ->assertRedirectToRoute('login');

        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'name' => 'Murid Demo A', 'username' => null]);
    }

    public function test_registration_rejects_username_supplied_by_the_client(): void
    {
        $this->postJson(route('register'), [...$this->registrationData(), 'username' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    #[TestWith(['role', 'admin'])]
    #[TestWith(['role', 'teacher'])]
    #[TestWith(['role', null])]
    #[TestWith(['status', 'active'])]
    #[TestWith(['approved_by', 1])]
    #[TestWith(['approved_at', '2026-09-09 00:00:00'])]
    #[TestWith(['user_id', 1])]
    #[TestWith(['student_id', 1])]
    #[TestWith(['student_profile_id', 1])]
    #[TestWith(['email_verified_at', '2026-09-09'])]
    public function test_rejects_privileged_registration_fields(string $field, mixed $value): void
    {
        $data = [...$this->registrationData(), $field => $value];

        $this->postJson(route('register'), $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_required_registration_fields_are_validated(): void
    {
        $this->postJson(route('register'), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'major_id', 'password'])
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_registration_rejects_a_spoofed_major_id(): void
    {
        $data = [...$this->registrationData(), 'major_id' => 999999];

        $this->postJson(route('register'), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('major_id');

        $this->assertDatabaseCount('users', 0);
    }

    #[TestWith(['email', 'invalid'])]
    #[TestWith(['email', ['student@example.com']])]
    #[TestWith(['password', 'short'])]
    public function test_rejects_invalid_registration_fields(string $field, mixed $value): void
    {
        $data = [...$this->registrationData(), $field => $value];

        $this->postJson(route('register'), $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('users', 0);
    }

    #[TestWith(['abcdefgh'])]
    #[TestWith(['ABCDEFGH'])]
    #[TestWith(['!!!!!!!!'])]
    #[TestWith(['abcdefghijk'])]
    public function test_registration_rejects_passwords_shorter_than_twelve_characters(string $password): void
    {
        $this->postJson(route('register'), [...$this->registrationData(), 'password' => $password])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Kata sandi minimal 12 karakter.');
    }

    #[TestWith(['abcdefghijkl'])]
    #[TestWith(['kata sandi sekolah yang aman'])]
    public function test_registration_accepts_twelve_character_passwords_without_composition_requirements(string $password): void
    {
        $this->post(route('register'), [...$this->registrationData(), 'password' => $password])
            ->assertRedirectToRoute('login');

        $this->assertDatabaseHas('users', ['email' => 'student@example.com']);
    }

    public function test_rejects_passwords_exceeding_bcrypt_byte_limit(): void
    {
        $data = [...$this->registrationData(), 'password' => str_repeat('é', 36).'A1'];

        $this->postJson(route('register'), $data)->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'The password must not exceed 72 bytes.');
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        User::factory()->create(['email' => 'student@example.com']);

        $this->postJson(route('register'), [...$this->registrationData(), 'email' => 'STUDENT@EXAMPLE.COM'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_registered_name_can_sign_in_after_admin_approval(): void
    {
        $this->post(route('register'), [...$this->registrationData(), 'name' => 'Murid Demo A'])->assertRedirectToRoute('login');
        $student = User::query()->where('email', 'student@example.com')->firstOrFail();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('student-approvals.store', $student))->assertRedirectToRoute('student-approvals.index');
        $this->post(route('logout'));

        $this->post(route('login'), ['identifier' => 'Murid Demo A', 'password' => 'kata sandi panjang'])->assertRedirectToRoute('account');
        $this->assertAuthenticatedAs($student);
    }

    public function test_registration_is_throttled(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson(route('register'), [])->assertUnprocessable();
        }

        $this->postJson(route('register'), [])->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak percobaan. Silakan tunggu sebentar lalu coba lagi.');
    }

    public function test_authenticated_users_cannot_register_another_account(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('register'), $this->registrationData())->assertRedirectToRoute('account');
    }

    public function test_registration_requires_csrf_token(): void
    {
        $this->app['env'] = 'production';
        $this->post(route('register'), [])->assertStatus(419);
    }

    /** @return array<string, int|string> */
    private function registrationData(): array
    {
        return [
            'name' => 'Murid Demo A',
            'email' => 'student@example.com',
            'major_id' => Major::factory()->create()->id,
            'password' => 'kata sandi panjang',
        ];
    }
}
