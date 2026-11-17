<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        foreach (['penjualan', 'pembelian', 'nota_layanan'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->constrained('karyawan')->restrictOnDelete();
                $table->string('employee_name')->nullable();
            });
        }
    }
    public function down(): void
    {
        foreach (['penjualan', 'pembelian', 'nota_layanan'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('employee_id');
                $table->dropColumn('employee_name');
            });
        }
        Schema::dropIfExists('karyawan');
    }
};
