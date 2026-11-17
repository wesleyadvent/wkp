<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalender_cabang', fn (Blueprint $t) => $t->boolean('pencatatan_lengkap')->nullable());
        Schema::create('inventory_forecasts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('kategori_barang')->cascadeOnDelete();
            $t->string('kadar', 50);
            $t->date('cutoff');
            $t->unsignedInteger('horizon');
            $t->string('method', 10)->default('ARIMA');
            $t->string('version', 40);
            $t->string('input_hash', 64);
            $t->string('status', 30);
            $t->double('total')->nullable();
            $t->json('details');
            $t->timestamps();
            $t->unique(['branch_id', 'category_id', 'kadar', 'cutoff', 'horizon', 'method', 'version'], 'forecast_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_forecasts');
        Schema::table('kalender_cabang', fn (Blueprint $t) => $t->dropColumn('pencatatan_lengkap'));
    }
};
