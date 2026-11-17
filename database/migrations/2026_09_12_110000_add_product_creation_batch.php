<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->char('creation_batch', 26)->nullable()->index();
        });
        DB::table('barang')->where(function ($query) {
            $query->whereNull('kode_barang')->orWhereRaw("TRIM(kode_barang) = ''");
        })->orderBy('id')->chunkById(200, function ($products) {
            foreach ($products as $product) {
                DB::table('barang')->where('id', $product->id)->update(['kode_barang' => 'WK-'.Str::ulid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', fn (Blueprint $table) => $table->dropColumn('creation_batch'));
    }
};
