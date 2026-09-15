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
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('nis', 30)->nullable()->change();
            $table->foreignId('school_class_id')->nullable()->change();
            $table->foreignId('pkl_place_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropForeign(['pkl_place_id']);
            $table->dropColumn('pkl_place_id');
        });
    }
};
