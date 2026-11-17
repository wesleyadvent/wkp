<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kalender_cabang', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->date('tanggal');
            $table->boolean('buka');
            $table->primary(['branch_id', 'tanggal']);
        });
        Schema::create('harga_antam_harian', function (Blueprint $table) {
            $table->date('tanggal')->primary();
            $table->decimal('harga', 15, 2);
            $table->date('tanggal_sumber');
            $table->string('sumber');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('harga_antam_harian');
        Schema::dropIfExists('kalender_cabang');
    }
};
