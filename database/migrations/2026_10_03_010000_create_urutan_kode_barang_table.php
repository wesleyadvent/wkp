<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urutan_kode_barang', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('kategori_barang_id')->constrained('kategori_barang')->cascadeOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unique(['branch_id', 'kategori_barang_id'], 'urutan_kode_barang_cabang_kategori_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urutan_kode_barang');
    }
};
