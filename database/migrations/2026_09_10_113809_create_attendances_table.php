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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7);
            $table->decimal('check_in_longitude', 10, 7);
            $table->decimal('check_in_accuracy', 8, 2);
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->string('check_in_selfie_path');
            $table->string('check_out_selfie_path')->nullable();
            $table->string('status')->default('hadir');
            $table->string('late_status')->nullable();
            $table->string('check_in_location_status')->nullable();
            $table->string('check_out_location_status')->nullable();
            $table->timestamps();

            $table->unique(['student_profile_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
