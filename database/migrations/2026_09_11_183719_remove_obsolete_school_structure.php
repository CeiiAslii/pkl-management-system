<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('pkl_place_id');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn(['check_in_location_status', 'check_out_location_status']);
        });

        Schema::dropIfExists('pkl_assignments');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('pkl_places');
        Schema::dropIfExists('academic_years');
    }

    public function down(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('registration_open')->default(false);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('major_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unique(['academic_year_id', 'major_id', 'name'], 'school_classes_year_major_name_unique');
            $table->timestamps();
        });

        Schema::create('pkl_places', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreignId('school_class_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('pkl_place_id')->nullable()->constrained()->restrictOnDelete();
        });

        Schema::create('pkl_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('pkl_place_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unique(['student_profile_id', 'academic_year_id'], 'pkl_assignments_student_year_unique');
            $table->timestamps();
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('check_in_location_status')->nullable();
            $table->string('check_out_location_status')->nullable();
        });

        DB::table('student_profiles')->whereNotNull('pkl_place_name')->orderBy('id')->get()->each(function (object $profile): void {
            $placeId = DB::table('pkl_places')->insertGetId([
                'name' => $profile->pkl_place_name,
                'address' => $profile->pkl_address ?? '',
                'contact_name' => $profile->pkl_contact_name,
                'phone' => $profile->pkl_contact_phone,
                'is_active' => true,
                'submitted_by' => $profile->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('student_profiles')->where('id', $profile->id)->update(['pkl_place_id' => $placeId]);
        });
    }
};
