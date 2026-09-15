<?php

namespace Tests\Feature;

use App\Actions\GuardStaffChange;
use App\Actions\UpdateTeacher;
use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_promotes_and_demotes_staff_without_losing_profile_or_password(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = TeacherProfile::factory()->create();
        $staff = $profile->user;
        $hash = $staff->password;
        $this->actingAs($admin)->put(route('admin.teachers.update', $staff), $this->data($staff, 'admin'))->assertSessionHasNoErrors();
        $this->assertSame(Role::Admin, $staff->fresh()->role);
        $this->assertSame($hash, $staff->fresh()->password);
        $this->assertModelExists($profile);
        $this->actingAs($staff->fresh())->get(route('admin.dashboard'))->assertOk();
        $this->get(route('teacher.dashboard'))->assertForbidden();

        $this->actingAs($admin)->put(route('admin.teachers.update', $staff), $this->data($staff, 'teacher'))->assertSessionHasNoErrors();
        $this->assertSame(Role::Teacher, $staff->fresh()->role);
        $this->actingAs($staff->fresh())->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('teacher.dashboard'))->assertOk();
    }

    public function test_staff_session_is_invalidated_when_promoted_to_admin(): void
    {
        $staff = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');

        app(UpdateTeacher::class)->handle($admin, $staff, [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => '',
            'status' => 'active',
            'role' => 'admin',
        ], app(GuardStaffChange::class));
        $this->app['auth']->forgetGuards();

        $this->get(route('teacher.dashboard'))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Keamanan akun Anda telah diperbarui. Silakan masuk kembali.');
        $this->assertGuest();

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_staff_session_is_invalidated_when_demoted_to_teacher(): void
    {
        $staff = User::factory()->admin()->create();
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');

        app(UpdateTeacher::class)->handle($admin, $staff, [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => '',
            'status' => 'active',
            'role' => 'teacher',
        ], app(GuardStaffChange::class));
        $this->app['auth']->forgetGuards();

        $this->get(route('admin.dashboard'))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Keamanan akun Anda telah diperbarui. Silakan masuk kembali.');
        $this->assertGuest();

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->get(route('teacher.dashboard'))->assertOk();
    }

    public function test_staff_session_is_invalidated_when_status_changes(): void
    {
        $staff = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');

        app(UpdateTeacher::class)->handle($admin, $staff, [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => '',
            'status' => 'suspended',
        ], app(GuardStaffChange::class));
        $this->app['auth']->forgetGuards();

        $this->get(route('account'))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Keamanan akun Anda telah diperbarui. Silakan masuk kembali.');
        $this->assertGuest();
    }

    public function test_harmless_student_profile_edit_does_not_invalidate_session(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $authVersion = $profile->user->auth_version;

        $this->post(route('login'), ['identifier' => $profile->user->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->patch(route('student.profile.update'), [
            'name' => 'Nama Profil Diperbarui',
            'email' => $profile->user->email,
            'phone' => '0800000000',
        ])->assertRedirectToRoute('student.profile.show');

        $this->assertSame($authVersion, $profile->user->fresh()->auth_version);
        $this->get(route('student.dashboard'))->assertOk();
    }

    public function test_admin_password_reset_invalidates_the_old_session_and_new_password_restores_access(): void
    {
        $staff = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $authVersion = $staff->fresh()->auth_version;

        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $oldToken = session()->token();
        $this->withSession(['private_value' => 'secret']);
        $oldStaffSession = session()->all();

        $this->actingAs($admin)->put(route('admin.teachers.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => 'kata sandi baru aman',
            'status' => 'active',
        ])->assertRedirectToRoute('admin.teachers.show', $staff);

        $this->assertSame($authVersion + 1, $staff->fresh()->auth_version);
        $this->app['auth']->forgetGuards();
        $this->withSession($oldStaffSession);
        $this->get(route('teacher.dashboard'))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Keamanan akun Anda telah diperbarui. Silakan masuk kembali.')
            ->assertSessionMissing('private_value');
        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());

        $this->postJson(route('login'), ['identifier' => $staff->email, 'password' => 'password'])
            ->assertUnprocessable();
        $this->post(route('login'), ['identifier' => $staff->email, 'password' => 'kata sandi baru aman'])
            ->assertRedirectToRoute('account');
        $this->get(route('teacher.dashboard'))->assertOk();
    }

    public function test_intentional_password_change_uses_laravel_hashing(): void
    {
        $staff = User::factory()->teacher()->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.teachers.update', $staff), [
            ...$this->data($staff), 'password' => 'kata sandi baru aman',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('kata sandi baru aman', $staff->fresh()->password));
    }

    #[TestWith(['teacher', 'active'])]
    #[TestWith(['admin', 'suspended'])]
    #[TestWith(['admin', 'active'])]
    public function test_protected_admin_cannot_be_changed_through_staff_workflow(string $role, string $status): void
    {
        $protected = User::factory()->admin()->create(['is_protected_admin' => true]);
        $originalName = $protected->name;
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.teachers.update', $protected), [...$this->data($protected, $role), 'status' => $status, 'name' => 'Changed'])
            ->assertForbidden();
        $this->assertSame($originalName, $protected->fresh()->name);
        $this->assertTrue($protected->fresh()->isAdmin());
    }

    public function test_protected_admin_is_not_listed_and_cannot_be_deleted_or_opened_for_edit(): void
    {
        $protected = User::factory()->admin()->create(['is_protected_admin' => true, 'name' => 'Protected Bootstrap']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.teachers.index'))->assertDontSee('Protected Bootstrap');
        $this->get(route('admin.teachers.edit', $protected))->assertForbidden();
        $this->delete(route('admin.teachers.destroy', $protected))->assertForbidden();
        $this->assertNotSoftDeleted($protected);
    }

    #[TestWith(['teacher', 'active'])]
    #[TestWith(['admin', 'suspended'])]
    public function test_last_active_admin_cannot_demote_or_suspend_itself(string $role, string $status): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(['status' => 'suspended']);
        $deleted = User::factory()->admin()->create();
        $deleted->delete();
        $this->actingAs($admin)->putJson(route('admin.teachers.update', $admin), [...$this->data($admin, $role), 'status' => $status])
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_last_admin_cannot_be_deleted_but_other_unprotected_staff_can(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->deleteJson(route('admin.teachers.destroy', $admin))->assertUnprocessable();
        $other = User::factory()->admin()->create();
        $this->delete(route('admin.teachers.destroy', $other))->assertRedirect();
        $this->assertSoftDeleted($other);
        $this->assertNotSoftDeleted($admin);
    }

    #[TestWith(['teacher'])]
    #[TestWith(['student'])]
    public function test_non_admin_cannot_change_roles(string $role): void
    {
        $user = $role === 'teacher' ? User::factory()->teacher()->create() : User::factory()->approvedStudent()->create();
        $this->actingAs($user)->put(route('admin.teachers.update', $user), $this->data($user, 'admin'))->assertForbidden();
        $this->assertSame($role, $user->fresh()->role->value);
    }

    public function test_staff_role_and_protection_flag_are_validated(): void
    {
        $staff = User::factory()->teacher()->create();
        $this->actingAs(User::factory()->admin()->create())->putJson(route('admin.teachers.update', $staff), [
            ...$this->data($staff, 'student'), 'is_protected_admin' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['role', 'is_protected_admin']);
        $this->assertSame(Role::Teacher, $staff->fresh()->role);
        $this->assertFalse($staff->fresh()->is_protected_admin);
    }

    /** @return array<string, string> */
    private function data(User $staff, string $role = 'teacher'): array
    {
        return ['name' => $staff->name, 'email' => $staff->email, 'role' => $role, 'status' => 'active', 'password' => ''];
    }
}
