<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cabang')->unique();
            $table->string('nama_cabang');
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('status')->default('aktif');
            $table->boolean('is_login_enabled')->default(true);
            $table->timestamp('login_disabled_at')->nullable();
            $table->foreignId('login_disabled_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->text('login_disabled_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('role')->default('toko_cabang')->after('password');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('cabang')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('branch_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('harga_emas', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('kadar');
            $table->decimal('harga_per_gram', 15, 2);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->string('nama_barang');
            $table->string('kategori');
            $table->string('kadar');
            $table->decimal('berat', 10, 3);
            $table->decimal('harga_per_gram', 15, 2);
            $table->decimal('ongkos', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('tersedia');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('jenis_potongan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_potongan');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_service');
            $table->decimal('harga_default', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota')->unique();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('pelanggan')->restrictOnDelete();
            $table->date('tanggal');
            $table->decimal('total_berat', 10, 3)->default(0);
            $table->decimal('total_ongkos', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->string('status')->default('valid');
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('item_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('penjualan')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('barang')->restrictOnDelete();
            $table->decimal('berat', 10, 3);
            $table->decimal('harga_per_gram', 15, 2);
            $table->decimal('ongkos', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota')->unique();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('pelanggan')->restrictOnDelete();
            $table->string('sumber_barang')->default('barang_luar');
            $table->foreignId('sale_id_ref')->nullable()->constrained('penjualan')->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('total_berat', 10, 3)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->string('status')->default('valid');
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('item_pembelian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('pembelian')->cascadeOnDelete();
            $table->foreignId('product_id_ref')->nullable()->constrained('barang')->nullOnDelete();
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('kadar')->nullable();
            $table->decimal('berat', 10, 3);
            $table->decimal('harga_per_gram_beli', 15, 2);
            $table->decimal('potongan_per_gram', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('potongan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('pembelian')->cascadeOnDelete();
            $table->foreignId('deduction_type_id')->nullable()->constrained('jenis_potongan')->nullOnDelete();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('nota_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota')->unique();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('pelanggan')->restrictOnDelete();
            $table->foreignId('product_id_ref')->nullable()->constrained('barang')->nullOnDelete();
            $table->string('asal_barang')->default('barang_luar');
            $table->string('nama_barang_manual')->nullable();
            $table->decimal('berat', 10, 3)->nullable();
            $table->foreignId('jenis_service_id')->constrained('jenis_layanan')->restrictOnDelete();
            $table->decimal('harga_service', 15, 2)->default(0);
            $table->string('status_service')->default('diterima');
            $table->date('tanggal_masuk');
            $table->date('estimasi_selesai')->nullable();
            $table->date('tanggal_ambil')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pembatalan_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type');
            $table->unsignedBigInteger('transaction_id');
            $table->text('alasan');
            $table->foreignId('verified_admin_id')->constrained('pengguna')->restrictOnDelete();
            $table->timestamp('cancelled_at');
            $table->timestamps();
            $table->index(['transaction_type', 'transaction_id']);
        });

        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('action');
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('log_login_cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->boolean('old_status');
            $table->boolean('new_status');
            $table->text('alasan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_login_cabang');
        Schema::dropIfExists('log_audit');
        Schema::dropIfExists('pembatalan_transaksi');
        Schema::dropIfExists('nota_layanan');
        Schema::dropIfExists('potongan_pembelian');
        Schema::dropIfExists('item_pembelian');
        Schema::dropIfExists('pembelian');
        Schema::dropIfExists('item_penjualan');
        Schema::dropIfExists('penjualan');
        Schema::dropIfExists('jenis_layanan');
        Schema::dropIfExists('jenis_potongan');
        Schema::dropIfExists('barang');
        Schema::dropIfExists('harga_emas');
        Schema::dropIfExists('pelanggan');
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('cabang');
    }
};
