<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table): void {
            $table->foreignId('kategori_barang_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('kategori_barang')
                ->restrictOnDelete();
        });

        // Preserve every existing item: categories from old free-text data that
        // are absent from the master are first promoted into the master table.
        foreach (DB::table('barang')->select('id', 'kategori')->orderBy('id')->cursor() as $product) {
            $categoryId = DB::table('kategori_barang')
                ->where('nama_kategori', $product->kategori)
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('kategori_barang')->insertGetId([
                    'nama_kategori' => $product->kategori,
                    'keterangan' => 'Dibuat otomatis dari data barang lama.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('barang')->where('id', $product->id)->update(['kategori_barang_id' => $categoryId]);
        }

        Schema::table('barang', function (Blueprint $table): void {
            $table->foreignId('kategori_barang_id')->nullable(false)->change();
            $table->dropColumn('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table): void {
            $table->string('kategori')->nullable()->after('nama_barang');
        });

        foreach (DB::table('barang')->select('barang.id', 'kategori_barang.nama_kategori')
            ->join('kategori_barang', 'kategori_barang.id', '=', 'barang.kategori_barang_id')
            ->cursor() as $product) {
            DB::table('barang')->where('id', $product->id)->update(['kategori' => $product->nama_kategori]);
        }

        Schema::table('barang', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kategori_barang_id');
        });
    }
};
