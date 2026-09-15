<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_protected_admin')->default(false);
        });

        $bootstrapId = DB::table('users')->where('role', 'admin')->whereNull('deleted_at')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('id')->value('id');
        if ($bootstrapId !== null) {
            DB::table('users')->where('id', $bootstrapId)->update(['is_protected_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_protected_admin');
        });
    }
};
