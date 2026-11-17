<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urutan_nomor_nota', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->string('prefix', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unique(['branch_id', 'prefix', 'year'], 'urutan_nomor_nota_cabang_prefix_tahun_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urutan_nomor_nota');
    }
};
