<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\TeacherNote;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTeacherManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_teacher_with_a_long_passphrase(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.teachers.create'))
            ->assertOk()
            ->assertSee('Nama lengkap')
            ->assertSee('Email')
            ->assertSee('Kata sandi')
            ->assertSee('Kata sandi minimal 12 karakter.')
            ->assertDontSee('name="username"', false);

        $this->post(route('admin.teachers.store'), [
            'name' => 'Guru Baru',
            'email' => 'GURU.BARU@example.test',
            'password' => 'kata sandi guru aman',
        ])->assertRedirect();

        $teacher = User::query()->where('email', 'guru.baru@example.test')->firstOrFail();
        $this->assertSame(Role::Teacher, $teacher->role);
        $this->assertSame(AccountStatus::Active, $teacher->status);
        $this->assertNull($teacher->username);
        $this->assertTrue(Hash::check('kata sandi guru aman', $teacher->password));
        $this->assertModelExists($teacher->teacherProfile);
    }

    public function test_teacher_creation_rejects_duplicates_short_password_and_privileged_fields(): void
    {
        $existing = User::factory()->teacher()->create(['email' => 'existing@example.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.teachers.store'), [
            'name' => 'Guru',
            'email' => $existing->email,
            'username' => 'tidak-boleh-dikirim',
            'password' => 'short',
            'role' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'username', 'password', 'role']);

        $this->assertSame(1, User::query()->where('role', Role::Teacher)->count());
    }

    public function test_teacher_password_cannot_exceed_bcrypt_byte_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $password = str_repeat('é', 37);

        $this->actingAs($admin)->postJson(route('admin.teachers.store'), [
            'name' => 'Guru Kata Sandi Panjang',
            'email' => 'guru.panjang@example.test',
            'password' => $password,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Kata sandi maksimal 72 byte.');

        $this->putJson(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'password' => $password,
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'Kata sandi maksimal 72 byte.');

        $this->assertDatabaseMissing('users', ['email' => 'guru.panjang@example.test']);
        $this->assertTrue(Hash::check('password', $teacher->fresh()->password));
    }

    public function test_teacher_creation_and_password_update_reject_eight_character_passwords(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $originalPassword = $teacher->password;

        $this->actingAs($admin)->postJson(route('admin.teachers.store'), [
            'name' => 'Guru Lemah',
            'email' => 'guru.lemah@example.test',
            'password' => 'abcdefgh',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Kata sandi minimal 12 karakter.');

        $this->putJson(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'password' => '!!!!!!!!',
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Kata sandi minimal 12 karakter.');

        $this->assertDatabaseMissing('users', ['email' => 'guru.lemah@example.test']);
        $this->assertSame($originalPassword, $teacher->fresh()->password);
    }

    public function test_admin_can_update_and_suspend_teacher_without_changing_password_or_role(): void
    {
        $teacher = User::factory()->teacher()->create();
        $oldPassword = $teacher->password;

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.teachers.update', $teacher), [
            'name' => 'Guru Diperbarui',
            'email' => 'guru.updated@example.test',
            'password' => '',
            'status' => 'suspended',
        ])->assertRedirectToRoute('admin.teachers.show', $teacher);

        $teacher->refresh();
        $this->assertSame('Guru Diperbarui', $teacher->name);
        $this->assertSame(Role::Teacher, $teacher->role);
        $this->assertSame(AccountStatus::Suspended, $teacher->status);
        $this->assertSame($oldPassword, $teacher->password);

        auth()->logout();
        $this->postJson(route('login'), ['identifier' => 'Guru Diperbarui', 'password' => 'password'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_admin_can_soft_delete_teacher_without_orphaning_profile_and_notes(): void
    {
        $profile = TeacherProfile::factory()->create();
        $note = TeacherNote::factory()->for($profile)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.teachers.destroy', $profile->user))
            ->assertRedirectToRoute('admin.teachers.index');

        $this->assertSoftDeleted('users', ['id' => $profile->user_id]);
        $this->assertDatabaseHas('teacher_profiles', ['id' => $profile->id]);
        $this->assertDatabaseHas('teacher_notes', ['id' => $note->id]);
        auth()->logout();
        $this->postJson(route('login'), ['identifier' => $profile->user->email, 'password' => 'password'])->assertUnprocessable();
    }

    public function test_non_admin_cannot_manage_teachers_or_spoof_a_student_as_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->approvedStudent()->create();
        $payload = ['name' => 'Tidak Sah', 'email' => 'invalid@example.test', 'password' => 'abcdefgh'];

        $this->actingAs($student)->post(route('admin.teachers.store'), $payload)->assertForbidden();
        $this->actingAs($teacher)->delete(route('admin.teachers.destroy', $teacher))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.teachers.show', $student))->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }
}
