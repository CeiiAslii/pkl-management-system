<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->text('content');
            $table->timestamps();
            $table->index(['student_profile_id', 'created_at']);
            $table->index(['teacher_profile_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_notes');
    }
};
