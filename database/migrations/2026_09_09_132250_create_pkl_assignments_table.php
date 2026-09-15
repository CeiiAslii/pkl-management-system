<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkl_assignments', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('pkl_assignments');
    }
};
