<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentPklLocationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_saves_location_without_optional_pic_and_completion_warning_disappears(): void
    {
        $profile = $this->student();
        $this->actingAs($profile->user)->get(route('student.dashboard'))
            ->assertSee('Profil belum lengkap')
            ->assertSee('Lengkapi data tempat PKL agar informasi PKL Anda lengkap.');

        $this->patch(route('student.profile.update'), $this->data($profile))
            ->assertSessionHasNoErrors()->assertRedirectToRoute('student.profile.show');

        $profile->refresh();
        $this->assertSame('1.0000000', $profile->pkl_latitude);
        $this->assertSame('2.0000000', $profile->pkl_longitude);
        $this->assertSame('12.50', $profile->pkl_location_accuracy);
        $this->assertSame('Tempat PKL Saya', $profile->pkl_place_name);
        $this->assertNull($profile->pkl_contact_name);
        $this->assertNull($profile->pkl_contact_phone);
        $this->assertNull($profile->profile_photo_path);
        $this->assertTrue($profile->isProfileComplete());
        $this->actingAs($profile->user->fresh())->get(route('student.dashboard'))->assertDontSee('Profil belum lengkap')->assertDontSee('Informasi PKL');
        $this->get(route('student.profile.show'))->assertSee('Lokasi tersimpan')->assertSee('Perbarui Lokasi')
            ->assertSee('https://www.google.com/maps/search/?api=1&query=1.0000000%2C2.0000000')
            ->assertDontSee('name="pkl_place_address"', false);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidLocations(): array
    {
        return [
            'latitude too low' => ['pkl_latitude', -90.0000001],
            'latitude too high' => ['pkl_latitude', 90.0000001],
            'longitude too low' => ['pkl_longitude', -180.0000001],
            'longitude too high' => ['pkl_longitude', 180.0000001],
            'latitude nonnumeric' => ['pkl_latitude', '<script>'],
            'longitude array' => ['pkl_longitude', [115]],
            'negative accuracy' => ['pkl_location_accuracy', -0.01],
            'excessive accuracy' => ['pkl_location_accuracy', 10000.01],
            'accuracy nonnumeric' => ['pkl_location_accuracy', 'unknown'],
            'missing latitude' => ['pkl_latitude', null],
            'missing longitude' => ['pkl_longitude', null],
            'missing accuracy' => ['pkl_location_accuracy', null],
            'location without name' => ['pkl_place_name', null],
            'oversized name' => ['pkl_place_name', str_repeat('a', 151)],
        ];
    }

    #[DataProvider('invalidLocations')]
    public function test_invalid_location_is_rejected_without_partial_profile_changes(string $field, mixed $value): void
    {
        $profile = $this->student();
        $this->actingAs($profile->user)->patchJson(route('student.profile.update'), [
            ...$this->data($profile), $field => $value, 'name' => 'Tidak Disimpan',
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertNull($profile->fresh()->pkl_latitude);
        $this->assertNotSame('Tidak Disimpan', $profile->user->fresh()->name);
    }

    /** @return array<string, array{int, int}> */
    public static function coordinateBoundaries(): array
    {
        return ['zero' => [0, 0], 'minimum' => [-90, -180], 'maximum' => [90, 180]];
    }

    #[DataProvider('coordinateBoundaries')]
    public function test_coordinate_boundaries_are_valid_and_zero_counts_as_complete(int $latitude, int $longitude): void
    {
        $profile = $this->student();
        $this->actingAs($profile->user)->patch(route('student.profile.update'), [
            ...$this->data($profile), 'pkl_latitude' => $latitude, 'pkl_longitude' => $longitude, 'pkl_location_accuracy' => 0,
        ])->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame((float) $latitude, (float) $profile->pkl_latitude);
        $this->assertSame((float) $longitude, (float) $profile->pkl_longitude);
        $this->assertTrue($profile->isProfileComplete());
    }

    /** @return array<string, array{string}> */
    public static function requiredCompletionFields(): array
    {
        return ['phone' => ['phone'], 'place' => ['pkl_place_name'], 'latitude' => ['pkl_latitude'], 'longitude' => ['pkl_longitude']];
    }

    #[DataProvider('requiredCompletionFields')]
    public function test_missing_required_field_keeps_profile_incomplete(string $field): void
    {
        $profile = $this->student();
        $profile->update(['phone' => '0800000000', 'pkl_place_name' => 'Tempat Saya', 'pkl_latitude' => 0, 'pkl_longitude' => 0, $field => null]);

        $this->assertFalse($profile->isProfileComplete());
        $this->actingAs($profile->user)->get(route('student.dashboard'))->assertSee('Profil belum lengkap');
    }

    public function test_location_update_rejects_another_profile_id_and_privileged_fields(): void
    {
        $victim = $this->student();
        $student = $this->student();
        $this->actingAs($student->user)->patchJson(route('student.profile.update'), [
            ...$this->data($student), 'student_profile_id' => $victim->id,
            'user_id' => $victim->user_id, 'role' => 'admin', 'status' => 'active', 'major_id' => 99,
            'approved_by' => $student->user_id, 'legacy_pkl_address' => 'Injected',
        ])->assertUnprocessable()->assertJsonValidationErrors(['student_profile_id', 'user_id', 'role', 'status', 'major_id', 'approved_by', 'legacy_pkl_address']);

        $this->assertNull($victim->fresh()->pkl_latitude);
        $this->assertNull($student->fresh()->pkl_latitude);
    }

    public function test_admin_updates_metadata_without_overwriting_gps_and_cannot_submit_coordinates(): void
    {
        $profile = $this->student();
        $profile->update(['pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5]);
        $data = ['name' => $profile->user->name, 'email' => $profile->user->email, 'major_id' => Major::factory()->create()->id, 'status' => 'active', 'pkl_place_name' => 'Nama Baru'];

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.students.update', $profile->user), $data)
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Nama Baru', $profile->fresh()->pkl_place_name);
        $this->assertSame('1.0000000', $profile->fresh()->pkl_latitude);
        $this->patchJson(route('admin.students.update', $profile->user), [
            ...$data, 'pkl_latitude' => 0, 'pkl_longitude' => 0, 'pkl_location_accuracy' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy']);
        $this->assertSame('2.0000000', $profile->fresh()->pkl_longitude);
    }

    public function test_empty_location_fields_do_not_erase_a_saved_location(): void
    {
        $profile = $this->student();
        $profile->update(['pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5]);
        $this->actingAs($profile->user)->patch(route('student.profile.update'), [
            ...$this->data($profile), 'pkl_latitude' => '', 'pkl_longitude' => '', 'pkl_location_accuracy' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1.0000000', $profile->fresh()->pkl_latitude);
        $this->assertSame('2.0000000', $profile->fresh()->pkl_longitude);
        $this->assertSame('12.50', $profile->fresh()->pkl_location_accuracy);
    }

    public function test_maps_are_only_visible_through_authorized_pages_and_legacy_address_is_hidden(): void
    {
        $profile = $this->student();
        $profile->forceFill(['pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'legacy_pkl_address' => 'Archived private address'])->save();
        $map = 'https://www.google.com/maps/search/?api=1&query=1.0000000%2C2.0000000';
        $adminUrl = route('admin.students.show', $profile->user);
        $teacherUrl = route('teacher.recap', ['student_profile_id' => $profile->id]);

        $this->get($adminUrl)->assertRedirectToRoute('login')->assertDontSee($map);
        $this->get($teacherUrl)->assertRedirectToRoute('login')->assertDontSee($map);
        $this->actingAs($this->student()->user)->get($adminUrl)->assertForbidden()->assertDontSee($map);
        $this->get($teacherUrl)->assertForbidden()->assertDontSee($map);
        $this->actingAs(User::factory()->admin()->create())->get($adminUrl)
            ->assertSee($map)->assertSee('rel="noopener noreferrer"', false)->assertDontSee('Archived private address');
        $this->actingAs(TeacherProfile::factory()->create()->user)->get($teacherUrl)
            ->assertSee($map)->assertDontSee('Archived private address');
        $this->assertArrayNotHasKey('legacy_pkl_address', $profile->toArray());
    }

    /** @return array<string, array{string}> */
    public static function usersDeniedFromProfile(): array
    {
        return ['admin' => ['admin'], 'teacher' => ['teacher'], 'pending student' => ['pending'], 'suspended student' => ['suspended']];
    }

    #[DataProvider('usersDeniedFromProfile')]
    public function test_only_active_students_can_open_or_save_the_gps_form(string $role): void
    {
        $profile = $this->student();
        $user = match ($role) {
            'admin' => User::factory()->admin()->create(),
            'teacher' => User::factory()->teacher()->create(),
            'pending' => User::factory()->create(),
            'suspended' => User::factory()->approvedStudent()->create(['status' => 'suspended']),
        };

        $this->actingAs($user)->get(route('student.profile.show'))->assertForbidden();
        $this->actingAs($user)->patchJson(route('student.profile.update'), $this->data($profile))->assertForbidden();
        $this->assertNull($profile->fresh()->pkl_latitude);
    }

    public function test_profile_renders_only_own_coordinates_and_escapes_pkl_metadata(): void
    {
        $profile = $this->student();
        $other = $this->student();
        $other->update(['pkl_latitude' => 50.1234567, 'pkl_longitude' => 120.1234567]);
        $profile->update(['pkl_place_name' => '<script>alert(1)</script>']);

        $this->actingAs($profile->user)->get(route('student.profile.show', ['student_profile_id' => $other->id]))
            ->assertSee('Ambil lokasi saya')
            ->assertDontSee('50.1234567')
            ->assertDontSee('120.1234567')
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function student(): StudentProfile
    {
        return StudentProfile::factory()->for(User::factory()->approvedStudent())->create(['pkl_contact_name' => null, 'pkl_contact_phone' => null]);
    }

    /** @return array<string, mixed> */
    private function data(StudentProfile $profile): array
    {
        return [
            'name' => $profile->user->name, 'email' => $profile->user->email, 'phone' => '080000000000',
            'pkl_place_name' => 'Tempat PKL Saya', 'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5,
        ];
    }
}
