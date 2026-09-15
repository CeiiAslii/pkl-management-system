<?php

namespace Tests\Feature\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_profile_relationships_and_direct_pkl_information_are_available(): void
    {
        $profile = StudentProfile::factory()->create([
            'pkl_place_name' => 'PT Telkom Indonesia',
            'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5,
        ]);

        $this->assertTrue($profile->user->studentProfile->is($profile));
        $this->assertSame('PT Telkom Indonesia', $profile->pkl_place_name);
        $this->assertSame('1.0000000', $profile->pkl_latitude);
    }

    public function test_new_users_default_to_pending_students_and_cannot_mass_assign_privileges(): void
    {
        $user = User::create([
            'name' => 'Student', 'email' => 'student@example.com', 'password' => 'LongPassword123',
            'role' => 'admin', 'status' => 'active', 'approved_by' => 999, 'approved_at' => now(),
        ])->fresh();

        $this->assertSame(Role::Student, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertNull($user->approved_by);
        $this->assertFalse($user->isActive());
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_database_prevents_duplicate_profiles(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->expectException(QueryException::class);
        StudentProfile::factory()->for($profile->user)->create();
    }

    public function test_database_prevents_duplicate_teacher_profiles(): void
    {
        $profile = TeacherProfile::factory()->create();

        $this->expectException(QueryException::class);
        TeacherProfile::factory()->for($profile->user)->create();
    }

    public function test_default_seeder_is_repeatable_and_only_creates_majors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('majors', 4);
        foreach (['TKJ', 'TSM', 'DPB', 'MP'] as $majorCode) {
            $this->assertDatabaseHas('majors', ['code' => $majorCode]);
        }
        $this->assertDatabaseCount('users', 0);
    }
}
