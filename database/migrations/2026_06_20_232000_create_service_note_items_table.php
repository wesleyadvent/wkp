<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_nota_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_note_id')->constrained('nota_layanan')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('jenis_layanan')->restrictOnDelete();
            $table->decimal('harga_service', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_nota_layanan');
    }
};
