<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->renameColumn('pkl_address', 'legacy_pkl_address');
            $table->decimal('pkl_latitude', 10, 7)->nullable();
            $table->decimal('pkl_longitude', 10, 7)->nullable();
            $table->decimal('pkl_location_accuracy', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->renameColumn('legacy_pkl_address', 'pkl_address');
            $table->dropColumn(['pkl_latitude', 'pkl_longitude', 'pkl_location_accuracy']);
        });
    }
};
