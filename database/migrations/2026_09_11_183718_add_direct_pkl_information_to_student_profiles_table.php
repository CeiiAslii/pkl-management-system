<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('pkl_place_name')->nullable()->after('address');
            $table->text('pkl_address')->nullable()->after('pkl_place_name');
            $table->string('pkl_contact_name')->nullable()->after('pkl_address');
            $table->string('pkl_contact_phone', 30)->nullable()->after('pkl_contact_name');
        });

        DB::table('student_profiles')->orderBy('id')->get()->each(function (object $profile): void {
            $placeId = $profile->pkl_place_id;

            if ($placeId === null) {
                $placeId = DB::table('pkl_assignments')
                    ->where('student_profile_id', $profile->id)
                    ->orderByDesc('starts_on')
                    ->orderByDesc('id')
                    ->value('pkl_place_id');
            }

            if ($placeId === null) {
                return;
            }

            $place = DB::table('pkl_places')->find($placeId);

            if ($place === null) {
                return;
            }

            DB::table('student_profiles')->where('id', $profile->id)->update([
                'pkl_place_name' => $place->name,
                'pkl_address' => $place->address,
                'pkl_contact_name' => $place->contact_name,
                'pkl_contact_phone' => $place->phone,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropColumn(['pkl_place_name', 'pkl_address', 'pkl_contact_name', 'pkl_contact_phone']);
        });
    }
};
