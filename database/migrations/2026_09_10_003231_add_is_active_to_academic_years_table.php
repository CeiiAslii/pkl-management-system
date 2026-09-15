<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table): void {
            $table->boolean('is_active')->default(false)->index()->after('registration_open');
        });

        $activeYear = DB::table('academic_years')->orderByDesc('starts_on')->value('id');

        if ($activeYear !== null) {
            DB::table('academic_years')->where('id', $activeYear)->update(['is_active' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table): void {
            $table->dropIndex('academic_years_is_active_index');
            $table->dropColumn('is_active');
        });
    }
};
