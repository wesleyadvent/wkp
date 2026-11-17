<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_layanan', function (Blueprint $table) {
            $table->foreignId('sale_id_ref')->nullable()->after('product_id_ref')->constrained('penjualan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nota_layanan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id_ref');
        });
    }
};
