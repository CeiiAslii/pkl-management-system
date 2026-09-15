<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StudentProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_read_and_update_own_contact_details(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->actingAs($profile->user)->get(route('student-profiles.show', $profile))->assertOk()->assertSee($profile->user->name);

        $this->patch(route('student-profiles.update', $profile), ['phone' => '+62 800-0000', 'address' => 'Alamat Demo'])
            ->assertRedirectToRoute('student-profiles.show', $profile);

        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'phone' => '+62 800-0000', 'address' => 'Alamat Demo']);
    }

    public function test_student_cannot_read_or_update_another_student_profile(): void
    {
        $profile = StudentProfile::factory()->create(['address' => 'Private address']);

        $this->actingAs(User::factory()->approvedStudent()->create())->get(route('student-profiles.show', $profile))
            ->assertNotFound()->assertDontSee('Private address');
        $this->patch(route('student-profiles.update', $profile), ['address' => 'Tampered'])->assertNotFound();

        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'address' => 'Private address']);
    }

    public function test_teacher_can_read_any_active_student_but_cannot_update_contacts(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $teacher = TeacherProfile::factory()->create();

        $this->actingAs($teacher->user)->get(route('student-profiles.show', $profile))->assertOk();
        $this->patch(route('student-profiles.update', $profile), ['phone' => '000'])->assertNotFound();

        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'phone' => $profile->phone]);
    }

    public function test_pending_student_is_denied_even_with_an_existing_session(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->actingAs($profile->user)->get(route('student-profiles.show', $profile))->assertForbidden();

        $this->assertGuest();
    }

    public function test_guest_cannot_read_a_profile(): void
    {
        $this->get(route('student-profiles.show', StudentProfile::factory()->create()))->assertRedirectToRoute('login');
    }

    #[TestWith(['role', 'admin'])]
    #[TestWith(['status', 'active'])]
    #[TestWith(['school_class_id', 999])]
    #[TestWith(['user_id', 999])]
    #[TestWith(['nis', '99999'])]
    #[TestWith(['phone', '<script>'])]
    #[TestWith(['address', ['invalid']])]
    public function test_profile_update_rejects_privileged_and_invalid_fields(string $field, mixed $value): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $original = $profile->fresh()->getAttributes();

        $this->actingAs($profile->user)->patchJson(route('student-profiles.update', $profile), [$field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($original, $profile->fresh()->getAttributes());
    }

    public function test_profile_escapes_name_and_address(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => '<script>name</script>']))
            ->create(['address' => '<script>address</script>']);

        $this->actingAs($profile->user)->get(route('student-profiles.show', $profile))
            ->assertSee($profile->user->name)->assertDontSee($profile->user->name, false)
            ->assertSee($profile->address)->assertDontSee($profile->address, false);
    }
}
