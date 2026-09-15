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
        Schema::table('pkl_places', function (Blueprint $table): void {
            $table->foreignId('submitted_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pkl_places', function (Blueprint $table): void {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn('submitted_by');
        });
    }
};
