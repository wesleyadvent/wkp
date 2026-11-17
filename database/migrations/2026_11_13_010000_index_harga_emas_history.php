<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('harga_emas', fn (Blueprint $table) => $table->index(['kadar', 'tanggal'], 'harga_emas_kadar_tanggal_index'));
    }

    public function down(): void
    {
        Schema::table('harga_emas', fn (Blueprint $table) => $table->dropIndex('harga_emas_kadar_tanggal_index'));
    }
};
