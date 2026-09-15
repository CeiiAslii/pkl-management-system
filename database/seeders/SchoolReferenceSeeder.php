<?php

namespace Database\Seeders;

use App\Models\Major;
use Illuminate\Database\Seeder;

class SchoolReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'TKJ' => 'Teknik Komputer dan Jaringan',
            'TSM' => 'Teknik Sepeda Motor',
            'DPB' => 'Desain dan Produksi Busana',
            'MP' => 'Manajemen Perkantoran',
        ] as $code => $name) {
            Major::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
