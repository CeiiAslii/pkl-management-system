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
            $table->decimal('latitude', 10, 7)->nullable()->after('phone');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geofence_radius')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pkl_places', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'geofence_radius']);
        });
    }
};
