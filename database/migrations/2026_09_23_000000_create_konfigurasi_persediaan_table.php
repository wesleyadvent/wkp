<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_persediaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->string('kategori', 100);
            $table->string('kadar', 50);
            $table->unsignedSmallInteger('lead_time_hari')->default(14);
            $table->unsignedInteger('safety_stock')->default(2);
            $table->timestamps();

            $table->unique(['branch_id', 'kategori', 'kadar'], 'konfigurasi_persediaan_kelompok_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_persediaan');
    }
};
