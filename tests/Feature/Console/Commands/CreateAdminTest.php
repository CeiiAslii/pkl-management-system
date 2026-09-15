<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['lowercase only', 'abcdefghijkl', 'lowercase@example.com'])]
    #[TestWith(['long passphrase', 'kata sandi sekolah aman', 'passphrase@example.com'])]
    public function test_command_accepts_twelve_character_passwords_without_character_class_requirements(
        string $description,
        string $password,
        string $email,
    ): void {
        $this->artisan('pkl:create-admin', ['--name' => 'Admin Demo', '--email' => $email])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutput('Administrator created.')
            ->assertSuccessful();

        $admin = User::where('email', $email)->firstOrFail();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->isActive());
        $this->assertTrue($admin->is_protected_admin);
        $this->assertTrue(Hash::check($password, $admin->password), $description);
    }

    public function test_command_cannot_promote_an_existing_student(): void
    {
        $student = User::factory()->create(['email' => 'student@example.com']);

        $this->artisan('pkl:create-admin', ['--name' => 'Admin', '--email' => 'student@example.com'])
            ->expectsQuestion('Password', 'StrongPassword123')
            ->expectsQuestion('Confirm password', 'StrongPassword123')
            ->expectsOutput('The email has already been taken.')
            ->assertFailed();

        $this->assertSame(Role::Student, $student->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }

    #[TestWith(['abcdefgh'])]
    #[TestWith(['ABCDEFGH'])]
    #[TestWith(['!!!!!!!!'])]
    public function test_command_rejects_passwords_shorter_than_twelve_characters(string $password): void
    {
        $this->artisan('pkl:create-admin', ['--name' => 'Admin', '--email' => 'admin@example.com'])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutput('Kata sandi minimal 12 karakter.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_requires_matching_password_confirmation(): void
    {
        $this->artisan('pkl:create-admin', ['--name' => 'Admin', '--email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'abcdefghijkl')
            ->expectsQuestion('Confirm password', 'different')
            ->expectsOutput('The password field confirmation does not match.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_requires_interactive_password_entry(): void
    {
        $this->artisan('pkl:create-admin', ['--no-interaction' => true])
            ->expectsOutput('Run this command interactively to enter the password securely.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
