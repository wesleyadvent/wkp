<?php

namespace Tests\Feature;

use App\Models\{Barang, Cabang, HargaEmas, JenisLayanan, JenisPotongan, Karyawan, KategoriBarang, NotaLayanan, Pelanggan, Pembelian, Pengguna, Penjualan};
use App\Services\DataConsistencyAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Cabang $branch;
    private Pelanggan $customer;
    private KategoriBarang $category;
    private JenisLayanan $service;
    private JenisPotongan $deduction;
    private array $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Pengguna::factory()->create(['role' => 'admin']);
        $this->branch = Cabang::create(['kode_cabang' => 'INT', 'nama_cabang' => 'Cabang Integritas']);
        $this->customer = Pelanggan::create(['nama' => 'Customer Awal', 'branch_id' => $this->branch->id]);
        $this->category = KategoriBarang::create(['nama_kategori' => 'Cincin', 'is_active' => true]);
        $this->service = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 10000, 'is_active' => true]);
        $this->deduction = JenisPotongan::create(['nama_potongan' => 'Potongan', 'nominal' => 1000, 'is_active' => true]);
        Karyawan::create(['nama' => 'Kasir Uji', 'is_active' => true]);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 100000]);
        $this->base = ['branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'employee_name' => 'Kasir Uji'];
        $this->actingAs($this->admin);
    }

    private function sale(): Penjualan
    {
        $product = Barang::create(['branch_id' => $this->branch->id, 'kategori_barang_id' => $this->category->id, 'nama_barang' => 'Cincin Awal', 'kadar' => '24K', 'berat' => 1, 'harga_per_gram' => 100000, 'ongkos' => 0, 'total_harga' => 100000]);
        $this->post(route('sales.store'), $this->base + ['product_id' => $product->id])->assertSessionHasNoErrors();

        return Penjualan::latest('id')->firstOrFail();
    }

    private function purchaseData(array $overrides = []): array
    {
        return array_replace($this->base + ['sumber_barang' => 'barang_luar', 'nama_barang' => 'Emas Luar', 'berat' => '1.001', 'harga_per_gram_beli' => '100000.25', 'potongan_per_gram' => '100.25', 'deduction_type_ids' => [$this->deduction->id], 'deduction_nominals' => ['1000.25']], $overrides);
    }

    private function serviceData(array $overrides = []): array
    {
        return array_replace($this->base + ['asal_barang' => 'barang_luar', 'nama_barang_manual' => 'Emas Luar', 'service_type_ids' => [$this->service->id], 'service_prices' => ['10000.25']], $overrides);
    }

    private function cancel(string $type, int $id): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('transactions.cancel'), ['transaction_type' => $type, 'transaction_id' => $id, 'admin_email' => $this->admin->email, 'admin_password' => 'password', 'alasan' => 'Uji konsistensi']);
    }

    public function test_purchase_rejects_excess_deductions_precision_and_misaligned_arrays(): void
    {
        foreach ([
            [['potongan_per_gram' => 100001], 'potongan_per_gram'],
            [['deduction_nominals' => [999999]], 'deduction_nominals'],
            [['berat' => '1.0001'], 'berat'],
            [['harga_per_gram_beli' => '100.001'], 'harga_per_gram_beli'],
            [['deduction_nominals' => [1, 2]], 'deduction_type_ids'],
            [['deduction_nominals' => [2 => 100]], 'deduction_nominals'],
        ] as [$data, $field]) {
            $this->postJson(route('purchases.store'), $this->purchaseData($data))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('pembelian', 0);
        $this->assertDatabaseCount('item_pembelian', 0);
    }

    public function test_purchase_uses_exact_decimal_totals_and_allows_zero_payment(): void
    {
        $this->post(route('purchases.store'), $this->purchaseData())->assertSessionHasNoErrors();
        $purchase = Pembelian::firstOrFail();
        $this->assertEquals('98999.65', $purchase->total_harga);
        $this->assertEquals('1100.60', $purchase->total_potongan);
        $this->assertEquals('99999.90', $purchase->items->first()->subtotal);
        $this->post(route('purchases.store'), $this->purchaseData(['berat' => 1, 'harga_per_gram_beli' => 1000, 'potongan_per_gram' => 0, 'deduction_nominals' => [1000]]))->assertSessionHasNoErrors();
        $this->assertEquals(0, Pembelian::latest('id')->first()->total_harga);
        $this->assertSame(0, app(DataConsistencyAudit::class)->run()['purchase_header_totals']['count']);
    }

    public function test_service_rejects_unpaired_prices_sparse_arrays_and_inactive_type(): void
    {
        $this->postJson(route('service-notes.store'), $this->serviceData(['service_prices' => [100, 200]]))->assertUnprocessable()->assertJsonValidationErrors('service_prices');
        $this->postJson(route('service-notes.store'), $this->serviceData(['service_type_ids' => [2 => $this->service->id]]))->assertUnprocessable()->assertJsonValidationErrors('service_type_ids');
        $this->service->update(['is_active' => false]);
        $this->postJson(route('service-notes.store'), $this->serviceData())->assertUnprocessable()->assertJsonValidationErrors('service_type_ids.0');
        $this->assertDatabaseCount('nota_layanan', 0);
        $this->assertDatabaseCount('item_nota_layanan', 0);
    }

    public function test_open_service_prevents_duplicate_service_buyback_and_sale_cancellation(): void
    {
        $sale = $this->sale();
        $data = $this->serviceData(['asal_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota]);
        $this->post(route('service-notes.store'), $data)->assertSessionHasNoErrors();
        $note = NotaLayanan::firstOrFail();
        $this->post(route('service-notes.store'), $data)->assertSessionHasErrors('nomor_nota');
        $buyback = $this->purchaseData(['sumber_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota]);
        $this->post(route('purchases.store'), $buyback)->assertSessionHasErrors('nomor_nota');
        $this->cancel('sale', $sale->id)->assertSessionHasErrors('transaction_id');
        $this->assertSame('terjual', $sale->items->first()->product->fresh()->status);
        $this->assertSame('valid', $sale->fresh()->status);
        $this->patch(route('service-notes.completed', $note))->assertSessionHasNoErrors();
        $this->patch(route('service-notes.picked-up', $note))->assertSessionHasNoErrors();
        $this->post(route('purchases.store'), $buyback)->assertSessionHasNoErrors();
        $this->post(route('purchases.store'), $buyback)->assertSessionHasErrors('nomor_nota');
        $this->assertDatabaseCount('pembelian', 1);
    }

    public function test_sale_cancellation_checks_dependencies_and_is_atomic_and_once_only(): void
    {
        $sale = $this->sale();
        $this->post(route('purchases.store'), $this->purchaseData(['sumber_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota]))->assertSessionHasNoErrors();
        $purchase = Pembelian::firstOrFail();
        $this->cancel('sale', $sale->id)->assertSessionHasErrors('transaction_id');
        $this->cancel('purchase', $purchase->id)->assertSessionHasNoErrors();
        $this->cancel('sale', $sale->id)->assertSessionHasNoErrors();
        $this->assertSame('tersedia', $sale->items->first()->product->fresh()->status);
        $this->cancel('sale', $sale->id)->assertSessionHasErrors('transaction_id');
        $this->assertDatabaseCount('pembatalan_transaksi', 2);
        $this->postJson(route('purchases.store'), $this->purchaseData(['sumber_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota]))->assertUnprocessable();
        $this->assertDatabaseCount('pembelian', 1);
    }

    public function test_service_status_cannot_skip_steps_or_regress_and_repeated_click_is_idempotent(): void
    {
        $this->post(route('service-notes.store'), $this->serviceData())->assertSessionHasNoErrors();
        $note = NotaLayanan::firstOrFail();
        $this->patch(route('service-notes.picked-up', $note))->assertSessionHasErrors('status_service');
        foreach (['completed', 'completed', 'picked-up', 'picked-up'] as $action) {
            $this->patch(route('service-notes.'.$action, $note))->assertSessionHasNoErrors();
        }
        $this->patch(route('service-notes.completed', $note))->assertSessionHasErrors('status_service');
        $this->assertSame('sudah_diambil', $note->fresh()->status_service);
        $this->assertNotNull($note->fresh()->tanggal_ambil);
        $this->assertDatabaseCount('log_audit', 3);
    }

    public function test_stale_gold_price_cannot_override_latest_price_or_receipt(): void
    {
        $sale = $this->sale();
        $product = Barang::create(['branch_id' => $this->branch->id, 'kategori_barang_id' => $this->category->id, 'nama_barang' => 'Stok', 'kadar' => '24K', 'berat' => 1, 'harga_per_gram' => 100000, 'total_harga' => 100000]);
        $old = HargaEmas::firstOrFail();
        $this->put(route('gold-prices.update', $old), ['tanggal' => today()->toDateString(), 'kadar' => '24K', 'harga_per_gram' => 200000])->assertSessionHasNoErrors();
        $this->put(route('gold-prices.update', $old), ['tanggal' => today()->subDay()->toDateString(), 'kadar' => '24K', 'harga_per_gram' => 50000])->assertSessionHasErrors('tanggal');
        $this->assertEquals(200000, $product->fresh()->harga_per_gram);
        $this->assertEquals(200000, $product->fresh()->total_harga);
        $this->assertEquals(100000, $sale->items->first()->fresh()->harga_per_gram);
        $this->put(route('gold-prices.update', $old), ['tanggal' => today()->toDateString(), 'kadar' => '22K', 'harga_per_gram' => 50000])->assertSessionHasErrors('kadar');
    }

    public function test_issued_receipt_fields_are_immutable_and_reference_api_uses_original_names(): void
    {
        $sale = $this->sale();
        $product = $sale->items->first()->product;
        $product->update(['nama_barang' => 'Nama Baru']);
        $this->customer->update(['nama' => 'Customer Baru']);
        $this->getJson(route('purchases.find-sale-query', ['nomor_nota' => $sale->nomor_nota]))->assertOk()->assertJsonPath('items.0.product.nama_barang', 'Cincin Awal')->assertJsonPath('customer.nama', 'Customer Awal');
        $this->get(route('sales.index', ['q' => 'Cincin Awal']))->assertOk()->assertSee($sale->nomor_nota);
        $this->get(route('sales.index', ['q' => 'Customer Awal']))->assertOk()->assertSee($sale->nomor_nota);
        $this->get(route('sales.index', ['q' => 'Nama Baru']))->assertOk()->assertDontSee($sale->nomor_nota);
        try {
            $sale->update(['total_harga' => 1]);
            $this->fail('Issued total was editable');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transaction', $exception->errors());
        }
        $this->assertEquals(100000, $sale->fresh()->total_harga);
    }

    public function test_inactive_admin_is_logged_out_and_cannot_authenticate_again(): void
    {
        $this->admin->update(['is_active' => false]);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login'), ['email' => $this->admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_branch_user_requires_branch_and_last_admin_cannot_be_deactivated(): void
    {
        $this->postJson(route('users.store'), ['name' => 'Tanpa Cabang', 'email' => 'nocabang@example.test', 'password' => 'password', 'role' => 'toko_cabang', 'is_active' => 1])->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->putJson(route('users.update', $this->admin), ['name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'admin', 'is_active' => 0])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_audit_detects_corrupt_totals_without_rewriting_records(): void
    {
        $sale = $this->sale();
        $results = app(DataConsistencyAudit::class)->run();
        $this->assertSame(0, array_sum(array_column($results, 'count')));
        DB::table('penjualan')->where('id', $sale->id)->update(['total_harga' => 1]);
        $results = app(DataConsistencyAudit::class)->run();
        $this->assertSame(1, $results['sale_header_totals']['count']);
        $this->assertContains($sale->id, $results['sale_header_totals']['sample_ids']);
        $this->assertEquals(1, $sale->fresh()->total_harga);
    }

    public function test_category_rename_keeps_inventory_configuration_linked_and_protects_used_masters(): void
    {
        $configuration = \App\Models\KonfigurasiPersediaan::create(['branch_id' => $this->branch->id, 'kategori' => $this->category->nama_kategori, 'kadar' => '24K', 'lead_time_hari' => 7, 'safety_stock' => 3]);
        $this->delete(route('product-categories.destroy', $this->category))->assertSessionHasErrors('delete');
        $this->put(route('product-categories.update', $this->category), ['nama_kategori' => 'Kategori Baru', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Kategori Baru', $configuration->fresh()->kategori);
        $this->assertEquals(7, $configuration->fresh()->lead_time_hari);
        $this->delete(route('gold-prices.destroy', HargaEmas::firstOrFail()))->assertSessionHasErrors('delete');
    }

    public function test_master_change_and_audit_log_are_one_atomic_operation(): void
    {
        $this->mock(\App\Services\AuditLogService::class, function ($mock): void {
            $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated audit failure'));
        });
        $this->putJson(route('deduction-types.update', $this->deduction), ['nama_potongan' => 'Nama Baru', 'nominal' => 2000, 'is_active' => 1])->assertStatus(500);
        $this->assertSame('Potongan', $this->deduction->fresh()->nama_potongan);
        $this->assertEquals(1000, $this->deduction->fresh()->nominal);
    }
}
