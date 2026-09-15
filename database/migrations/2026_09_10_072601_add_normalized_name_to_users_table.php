<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('normalized_name')->nullable()->after('name')->index();
        });

        DB::table('users')->select(['id', 'name'])->orderBy('id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'normalized_name' => Str::lower(Str::squish($user->name)),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_normalized_name_index');
            $table->dropColumn('normalized_name');
        });
    }
};
