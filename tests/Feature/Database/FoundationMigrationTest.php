<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationMigrationTest extends TestCase
{
    public function test_pkl_gps_migration_preserves_legacy_address_through_upgrade_and_rollback(): void
    {
        config(['database.connections.location_migration_test' => [
            ...config('database.connections.sqlite'), 'database' => ':memory:', 'url' => null,
        ]]);
        $options = ['--database' => 'location_migration_test', '--force' => true];
        $gpsMigration = 'database/migrations/2026_09_12_005311_add_pkl_gps_location_to_student_profiles_table.php';
        $earlierMigrations = array_values(array_filter(
            glob('database/migrations/*.php'),
            fn (string $path): bool => $path < $gpsMigration,
        ));
        $this->artisan('migrate', [...$options, '--path' => $earlierMigrations])->assertSuccessful();
        $database = DB::connection('location_migration_test');
        $userId = $database->table('users')->insertGetId([
            'name' => 'Murid Demo C', 'email' => 'legacy@example.test', 'password' => 'existing-hash',
        ]);
        $profileId = $database->table('student_profiles')->insertGetId([
            'user_id' => $userId, 'pkl_place_name' => 'Existing Place', 'pkl_address' => 'Existing address',
        ]);

        $this->artisan('migrate', [...$options, '--path' => $gpsMigration])->assertSuccessful();
        $profile = $database->table('student_profiles')->find($profileId);
        $this->assertSame('Existing address', $profile->legacy_pkl_address);
        $this->assertSame('Existing Place', $profile->pkl_place_name);
        $this->assertNull($profile->pkl_latitude);
        $this->assertNull($profile->pkl_longitude);
        $this->assertNull($profile->pkl_location_accuracy);

        $this->artisan('migrate:rollback', [...$options, '--step' => 1])->assertSuccessful();
        $this->assertSame('Existing address', $database->table('student_profiles')->find($profileId)->pkl_address);
        $this->assertFalse(Schema::connection('location_migration_test')->hasColumn('student_profiles', 'pkl_latitude'));
        DB::purge('location_migration_test');
    }

    public function test_protected_admin_migration_selects_bootstrap_account_and_rolls_back_safely(): void
    {
        config(['database.connections.staff_migration_test' => [
            ...config('database.connections.sqlite'), 'database' => ':memory:', 'url' => null,
        ]]);
        $options = ['--database' => 'staff_migration_test', '--force' => true];
        $migration = 'database/migrations/2026_09_12_013339_add_protected_admin_flag_to_users_table.php';
        $earlier = array_values(array_filter(glob('database/migrations/*.php'), fn (string $path): bool => $path < $migration));
        $this->artisan('migrate', [...$options, '--path' => $earlier])->assertSuccessful();
        $database = DB::connection('staff_migration_test');
        $database->table('users')->insert(['name' => 'Retired', 'email' => 'retired@example.test', 'password' => 'unchanged-hash', 'role' => 'admin', 'status' => 'active', 'deleted_at' => now()]);
        $database->table('users')->insert(['name' => 'Inactive', 'email' => 'inactive@example.test', 'password' => 'unchanged-hash', 'role' => 'admin', 'status' => 'suspended']);
        $bootstrapId = $database->table('users')->insertGetId(['name' => 'Bootstrap', 'email' => 'bootstrap@example.test', 'password' => 'unchanged-hash', 'role' => 'admin', 'status' => 'active']);
        $database->table('users')->insert(['name' => 'Later', 'email' => 'later@example.test', 'password' => 'unchanged-hash', 'role' => 'admin', 'status' => 'active']);
        $database->table('users')->insert(['name' => 'Teacher', 'email' => 'teacher@example.test', 'password' => 'unchanged-hash', 'role' => 'teacher', 'status' => 'active']);

        $this->artisan('migrate', [...$options, '--path' => $migration])->assertSuccessful();
        $this->assertSame([$bootstrapId], $database->table('users')->where('is_protected_admin', true)->pluck('id')->all());
        $this->assertSame(5, $database->table('users')->count());
        $this->assertSame('unchanged-hash', $database->table('users')->find($bootstrapId)->password);
        $this->artisan('migrate:rollback', [...$options, '--step' => 1])->assertSuccessful();
        $this->assertFalse(Schema::connection('staff_migration_test')->hasColumn('users', 'is_protected_admin'));
        $this->assertSame(5, $database->table('users')->count());
        $this->artisan('migrate', [...$options, '--path' => $migration])->assertSuccessful();
        $this->assertSame([$bootstrapId], $database->table('users')->where('is_protected_admin', true)->pluck('id')->all());
        DB::purge('staff_migration_test');
    }

    public function test_foundation_migrations_upgrade_and_rollback_without_losing_existing_users(): void
    {
        config(['database.connections.migration_test' => [
            ...config('database.connections.sqlite'), 'database' => ':memory:', 'url' => null,
        ]]);
        $options = ['--database' => 'migration_test', '--force' => true];
        $this->artisan('migrate', [...$options, '--path' => 'database/migrations/0001_01_01_000000_create_users_table.php'])
            ->assertSuccessful();
        $database = DB::connection('migration_test');
        $database->table('users')->insert([
            'name' => 'Pengguna Demo', 'email' => 'existing@example.com', 'password' => 'existing-password-hash',
        ]);

        $this->artisan('migrate', $options)->assertSuccessful();

        $user = $database->table('users')->where('email', 'existing@example.com')->first();
        $this->assertSame('student', $user->role);
        $this->assertSame('pending', $user->status);
        $this->assertNull($user->username);
        $this->assertSame('pengguna demo', $user->normalized_name);
        $this->assertSame('existing-password-hash', $user->password);
        $this->assertFalse(Schema::connection('migration_test')->hasTable('pkl_assignments'));
        $this->assertFalse(Schema::connection('migration_test')->hasTable('school_classes'));
        $this->assertFalse(Schema::connection('migration_test')->hasTable('pkl_places'));
        $this->assertFalse(Schema::connection('migration_test')->hasTable('academic_years'));
        $this->assertTrue(Schema::connection('migration_test')->hasTable('daily_reports'));
        $this->assertTrue(Schema::connection('migration_test')->hasTable('leave_requests'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('student_profiles', 'profile_photo_path'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('users', 'major_id'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('users', 'deleted_at'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('users', 'auth_version'));
        $this->assertSame(1, $user->auth_version);
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('student_profiles', 'pkl_place_name'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('student_profiles', 'legacy_pkl_address'));
        $this->assertFalse(Schema::connection('migration_test')->hasColumn('student_profiles', 'school_class_id'));
        $this->assertFalse(Schema::connection('migration_test')->hasColumn('student_profiles', 'pkl_place_id'));
        $this->assertTrue(Schema::connection('migration_test')->hasTable('teacher_notes'));
        $this->assertTrue(Schema::connection('migration_test')->hasColumn('leave_requests', 'reviewed_by'));

        $migrationSteps = count(glob(database_path('migrations/*.php'))) - 1;
        $this->artisan('migrate:rollback', [...$options, '--step' => $migrationSteps])->assertSuccessful();

        $this->assertFalse(Schema::connection('migration_test')->hasTable('pkl_assignments'));
        $this->assertFalse(Schema::connection('migration_test')->hasColumn('users', 'role'));
        $this->assertSame('existing-password-hash', $database->table('users')->where('email', 'existing@example.com')->value('password'));

        $this->artisan('migrate', $options)->assertSuccessful();
        $this->assertTrue(Schema::connection('migration_test')->hasTable('student_profiles'));
        DB::purge('migration_test');
    }
}
