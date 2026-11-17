<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('konfigurasi_persediaan', 'method')) {
            Schema::table('konfigurasi_persediaan', fn (Blueprint $table) => $table->string('method', 8)->default('SMA'));
        }
        // Buat indeks pengganti dengan branch_id di depan sebelum melepas
        // indeks lama yang juga dipakai foreign key cabang oleh MariaDB.
        if (! Schema::hasIndex('konfigurasi_persediaan', 'konfigurasi_metode_kelompok_unique')) {
            Schema::table('konfigurasi_persediaan', fn (Blueprint $table) => $table->unique(['branch_id', 'method', 'kategori', 'kadar'], 'konfigurasi_metode_kelompok_unique'));
        }
        if (Schema::hasIndex('konfigurasi_persediaan', 'konfigurasi_persediaan_kelompok_unique')) {
            Schema::table('konfigurasi_persediaan', fn (Blueprint $table) => $table->dropUnique('konfigurasi_persediaan_kelompok_unique'));
        }
        DB::transaction(function () {
            DB::table('konfigurasi_persediaan')->where('method', 'SMA')->orderBy('id')->chunkById(200, function ($rows) {
                $copies = [];
                foreach ($rows as $row) {
                    foreach (['WMA', 'ARIMA'] as $method) {
                        $copy = (array) $row;
                        unset($copy['id']);
                        $copy['method'] = $method;
                        $copies[] = $copy;
                    }
                }
                DB::table('konfigurasi_persediaan')->insertOrIgnore($copies);
            });
        });
    }

    public function down(): void
    {
        DB::table('konfigurasi_persediaan')->whereIn('method', ['WMA', 'ARIMA'])->delete();
        Schema::table('konfigurasi_persediaan', fn (Blueprint $table) => $table->unique(['branch_id', 'kategori', 'kadar'], 'konfigurasi_persediaan_kelompok_unique'));
        Schema::table('konfigurasi_persediaan', function (Blueprint $table) {
            $table->dropUnique('konfigurasi_metode_kelompok_unique');
            $table->dropColumn('method');
        });
    }
};
