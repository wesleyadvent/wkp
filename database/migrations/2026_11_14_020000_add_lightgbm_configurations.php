<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('konfigurasi_persediaan')->where('method', 'SMA')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $copy = (array) $row;
                unset($copy['id']);
                $copy['method'] = 'LightGBM';
                DB::table('konfigurasi_persediaan')->insertOrIgnore($copy);
            }
        });
    }

    public function down(): void
    {
        DB::table('konfigurasi_persediaan')->where('method', 'LightGBM')->delete();
    }
};
