<?php

namespace Tests\Feature;

use App\Models\{Barang, Cabang, HargaEmas, JenisLayanan, JenisPotongan, Karyawan, KategoriBarang, NotaLayanan, Pelanggan, Pembelian, Pengguna, Penjualan};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_receipts_and_reports_keep_original_master_data(): void
    {
        $admin = Pengguna::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $branch = Cabang::create(['kode_cabang' => 'SNAP', 'nama_cabang' => 'Cabang Awal']);
        $customer = Pelanggan::create(['branch_id' => $branch->id, 'nama' => 'Customer Awal', 'telepon' => '08111', 'alamat' => 'Alamat Awal']);
        $employee = Karyawan::create(['nama' => 'Kasir Awal', 'is_active' => true]);
        $category = KategoriBarang::create(['nama_kategori' => 'Kategori Awal', 'is_active' => true]);
        $gold = HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 1000000]);
        $product = Barang::create(['kode_barang' => 'SNAP-001', 'branch_id' => $branch->id, 'kategori_barang_id' => $category->id, 'nama_barang' => 'Barang Awal', 'kadar' => '24K', 'berat' => 2, 'harga_per_gram' => 1000000, 'ongkos' => 10000, 'total_harga' => 2010000]);
        $deduction = JenisPotongan::create(['nama_potongan' => 'Potongan Awal', 'nominal' => 5000, 'is_active' => true]);
        $service = JenisLayanan::create(['nama_service' => 'Service Awal', 'harga_default' => 15000, 'is_active' => true]);
        $base = ['employee_name' => $employee->nama, 'branch_id' => $branch->id, 'customer_id' => $customer->id];
        $this->post(route('sales.store'), $base + ['product_id' => $product->id])->assertSessionHasNoErrors();
        $sale = Penjualan::firstOrFail();
        $this->post(route('service-notes.store'), $base + ['asal_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota, 'service_type_ids' => [$service->id], 'service_prices' => [15000]])->assertSessionHasNoErrors();
        $note = NotaLayanan::firstOrFail();
        $this->patch(route('service-notes.completed', $note))->assertSessionHasNoErrors();
        $this->patch(route('service-notes.picked-up', $note))->assertSessionHasNoErrors();
        $this->post(route('purchases.store'), $base + ['sumber_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota, 'harga_per_gram_beli' => 900000, 'deduction_type_ids' => [$deduction->id], 'deduction_nominals' => [5000]])->assertSessionHasNoErrors();
        $purchase = Pembelian::firstOrFail();

        $this->put(route('deduction-types.update', $deduction), ['nama_potongan' => 'Potongan Baru', 'nominal' => 9000, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->put(route('service-types.update', $service), ['nama_service' => 'Service Baru', 'harga_default' => 99000, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->put(route('product-categories.update', $category), ['nama_kategori' => 'Kategori Baru', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->put(route('gold-prices.update', $gold), ['tanggal' => today()->toDateString(), 'kadar' => '24K', 'harga_per_gram' => 2000000])->assertSessionHasNoErrors();
        $product->update(['nama_barang' => 'Barang Baru', 'kadar' => '22K', 'harga_per_gram' => 2000000]);
        $customer->update(['nama' => 'Customer Baru', 'alamat' => 'Alamat Baru']);
        $branch->update(['nama_cabang' => 'Cabang Baru']);
        $employee->update(['nama' => 'Kasir Baru']);

        foreach (['sales.show', 'sales.print'] as $route) {
            $this->get(route($route, $sale))->assertOk()->assertSee('Barang Awal')->assertSee('Kategori Awal')->assertSee('Customer Awal')->assertSee('Cabang Awal')->assertSee('Kasir Awal')->assertSee('1.000.000')->assertDontSee('Barang Baru')->assertDontSee('Kategori Baru');
        }
        foreach (['purchases.show', 'purchases.print'] as $route) {
            $this->get(route($route, $purchase))->assertOk()->assertSee('Potongan Awal')->assertSee('Customer Awal')->assertSee('900.000')->assertDontSee('Potongan Baru');
        }
        foreach (['service-notes.show', 'service-notes.print'] as $route) {
            $this->get(route($route, $note))->assertOk()->assertSee('Service Awal')->assertSee('Barang Awal')->assertSee('15.000')->assertDontSee('Service Baru')->assertDontSee('Barang Baru');
        }
        $filters = ['start' => today()->toDateString(), 'end' => today()->toDateString(), 'print' => 1];
        $this->get(route('reports.sales', $filters))->assertOk()->assertSee('Barang Awal')->assertSee('Kategori Awal')->assertDontSee('Barang Baru');
        $this->get(route('reports.purchases', $filters))->assertOk()->assertSee('Potongan Awal')->assertDontSee('Potongan Baru');
        $this->get(route('reports.services', $filters))->assertOk()->assertSee('Service Awal')->assertDontSee('Service Baru');

        $snapshot = $note->fresh()->receipt_snapshot;
        $note->forceFill(['receipt_snapshot' => []])->save();
        $this->assertSame($snapshot, $note->fresh()->receipt_snapshot);
        $this->assertSame('Service Awal', $note->fresh()->receipt('serviceType')->nama_service);
        $this->assertEquals(2010000, $sale->fresh()->total_harga);
        $this->assertEquals(1795000, $purchase->fresh()->total_harga);

        foreach (['employees' => $employee, 'gold-prices' => $gold, 'product-categories' => $category, 'deduction-types' => $deduction, 'service-types' => $service] as $route => $master) {
            $this->get(route($route.'.index'))->assertOk()->assertSee('disabled aria-disabled="true"', false)->assertSee('Sudah digunakan')->assertDontSee('data-delete-url="'.route($route.'.destroy', $master).'"', false);
            $this->delete(route($route.'.destroy', $master))->assertSessionHasErrors('delete');
            $this->assertModelExists($master);
        }
    }

    public function test_migration_freezes_existing_receipts_without_changing_totals_or_timestamps(): void
    {
        $branch = Cabang::create(['kode_cabang' => 'OLD', 'nama_cabang' => 'Cabang Lama']);
        $customer = Pelanggan::create(['branch_id' => $branch->id, 'nama' => 'Customer Lama']);
        $type = JenisPotongan::create(['nama_potongan' => 'Potongan Lama', 'nominal' => 1000]);
        $purchase = Pembelian::create(['nomor_nota' => 'OLD-001', 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'tanggal' => today(), 'total_harga' => 100000]);
        $deduction = $purchase->deductions()->create(['deduction_type_id' => $type->id, 'nominal' => 1000]);
        $migration = require database_path('migrations/2026_10_22_000000_snapshot_receipt_master_data.php');
        $migration->down();
        $migration->up();
        $type->update(['nama_potongan' => 'Diubah']);
        $customer->update(['nama' => 'Diubah']);
        $migration->up();
        $this->assertSame('Potongan Lama', $deduction->fresh()->receipt('deductionType')->nama_potongan);
        $this->assertSame('Customer Lama', $purchase->fresh()->receipt('customer')->nama);
        $this->assertEquals(100000, $purchase->fresh()->total_harga);
        $this->assertEquals($purchase->updated_at, $purchase->fresh()->updated_at);
    }
}
