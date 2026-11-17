<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urutan_harian_barang', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unique(['date', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urutan_harian_barang');
    }
};
