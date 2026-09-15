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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->string('activity_photo_original_path')->nullable()->after('activity_photo_path');
            $table->boolean('had_photo')->default(false)->after('activity_photo_original_path');
            $table->uuid('photo_delivery_token')->nullable()->after('had_photo');
            $table->timestamp('photo_telegram_sent_at')->nullable()->after('photo_delivery_token');
        });

        DB::table('daily_reports')
            ->whereNotNull('activity_photo_path')
            ->update(['had_photo' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn([
                'activity_photo_original_path',
                'had_photo',
                'photo_delivery_token',
                'photo_telegram_sent_at',
            ]);
        });
    }
};
