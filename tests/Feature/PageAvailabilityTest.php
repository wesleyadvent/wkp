<?php

namespace Tests\Feature;

use App\Models\{Barang, Cabang, HargaEmas, JenisLayanan, JenisPotongan, Karyawan, KategoriBarang, NotaLayanan, Pelanggan, Pembelian, Pengguna, Penjualan};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_main_read_pages_forms_details_and_prints_render_for_their_roles(): void
    {
        $admin = Pengguna::factory()->create();
        $branch = Cabang::create(['kode_cabang' => 'WEB', 'nama_cabang' => 'Cabang Web']);
        $branchUser = Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]);
        $customer = Pelanggan::create(['nama' => 'Pelanggan Web', 'branch_id' => $branch->id]);
        $category = KategoriBarang::create(['nama_kategori' => 'Cincin', 'is_active' => true]);
        $gold = HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 100000]);
        $employee = Karyawan::create(['nama' => 'Kasir Web', 'is_active' => true]);
        $service = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 5000, 'is_active' => true]);
        $deduction = JenisPotongan::create(['nama_potongan' => 'Potongan Web', 'nominal' => 1000, 'is_active' => true]);
        $product = Barang::create(['branch_id' => $branch->id, 'kategori_barang_id' => $category->id, 'nama_barang' => 'Cincin Web', 'kadar' => '24K', 'berat' => 1, 'harga_per_gram' => 100000, 'total_harga' => 100000]);
        $base = ['branch_id' => $branch->id, 'customer_id' => $customer->id, 'employee_name' => $employee->nama];
        $this->actingAs($admin);
        $this->post(route('sales.store'), $base + ['product_id' => $product->id])->assertSessionHasNoErrors();
        $sale = Penjualan::firstOrFail();
        $this->post(route('purchases.store'), $base + ['sumber_barang' => 'barang_luar', 'nama_barang' => 'Emas Luar', 'berat' => 1, 'harga_per_gram_beli' => 90000, 'deduction_type_ids' => [$deduction->id], 'deduction_nominals' => [1000]])->assertSessionHasNoErrors();
        $purchase = Pembelian::firstOrFail();
        $this->post(route('service-notes.store'), $base + ['asal_barang' => 'barang_luar', 'nama_barang_manual' => 'Emas Service', 'service_type_ids' => [$service->id], 'service_prices' => [5000]])->assertSessionHasNoErrors();
        $note = NotaLayanan::firstOrFail();
        $stock = $product->replicate(['kode_barang']);
        $stock->status = 'tersedia';
        $stock->save();

        $common = [
            ['dashboard', []], ['profile.edit', []], ['products.index', ['branch_id' => $branch->id]],
            ['products.labels', ['ids' => [$product->id]]], ['products.barcode', $product],
            ['sales.index', []], ['sales.create', []], ['sales.show', $sale], ['sales.print', $sale],
            ['purchases.index', []], ['purchases.create', []], ['purchases.show', $purchase], ['purchases.print', $purchase],
            ['service-notes.index', []], ['service-notes.create', []], ['service-notes.show', $note], ['service-notes.print', $note],
            ['customers.index', []], ['customers.create', []], ['customers.edit', $customer],
            ['gold-prices.index', []], ['product-categories.index', []], ['deduction-types.index', []], ['service-types.index', []], ['employees.index', []],
            ['reports.sales', []], ['reports.purchases', []], ['reports.services', []], ['reports.profit-loss', []], ['inventory-analysis.index', []],
            ['reports.sales', ['print' => 1]], ['reports.purchases', ['print' => 1]], ['reports.services', ['print' => 1]],
        ];
        $adminOnly = [
            ['products.create', []], ['products.edit', $stock], ['branches.index', []], ['branches.create', []], ['branches.edit', $branch], ['branches.login-logs', []],
            ['users.index', []], ['users.create', []], ['users.edit', $branchUser], ['audit-logs.index', []],
            ['inventory-settings.index', []], ['inventory-settings.edit', ['branch_id' => $branch->id, 'kategori' => $category->nama_kategori, 'kadar' => '24K']],
            ['transactions.destroy-sales', ['branch_id' => $branch->id]], ['transactions.destroy-purchases', ['branch_id' => $branch->id]], ['transactions.destroy-services', ['branch_id' => $branch->id]],
        ];
        foreach (['gold-prices' => $gold, 'product-categories' => $category, 'deduction-types' => $deduction, 'service-types' => $service, 'employees' => $employee] as $prefix => $model) {
            $adminOnly[] = [$prefix.'.create', []];
            $adminOnly[] = [$prefix.'.edit', $model];
        }
        foreach ($common as [$route, $parameters]) {
            $this->get(route($route, $parameters))->assertOk();
        }
        foreach ($adminOnly as [$route, $parameters]) {
            $this->get(route($route, $parameters))->assertOk();
        }
        $this->actingAs($branchUser);
        foreach ($common as [$route, $parameters]) {
            $this->get(route($route, $parameters))->assertOk();
        }
        foreach ($adminOnly as [$route, $parameters]) {
            $this->get(route($route, $parameters))->assertForbidden();
        }
    }

    public function test_malformed_filters_return_validation_errors_instead_of_server_errors(): void
    {
        $this->actingAs(Pengguna::factory()->create());
        foreach (['dashboard', 'products.index', 'sales.index', 'purchases.index', 'service-notes.index', 'customers.index', 'gold-prices.index', 'product-categories.index', 'deduction-types.index', 'service-types.index', 'employees.index', 'users.index', 'branches.index', 'inventory-analysis.index', 'inventory-settings.index', 'reports.sales', 'reports.purchases', 'reports.services', 'reports.profit-loss', 'transactions.destroy-sales', 'transactions.destroy-purchases', 'transactions.destroy-services'] as $route) {
            $this->getJson(route($route, ['q' => ['invalid'], 'date' => 'not-a-date', 'branch_id' => ['invalid']]))->assertUnprocessable();
        }
    }
}
