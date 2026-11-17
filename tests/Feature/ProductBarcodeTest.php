<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Karyawan;
use App\Models\HargaEmas;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Penjualan;
use App\Models\Pengguna;
use App\Services\AuditLogService;
use App\Services\TransactionCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBarcodeTest extends TestCase
{
    use RefreshDatabase;

    private Cabang $branch;

    private Pengguna $admin;

    private KategoriBarang $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Cabang::create(['kode_cabang' => 'A', 'nama_cabang' => 'Cabang A']);
        $this->admin = Pengguna::factory()->create(['role' => 'admin', 'password' => 'secret123']);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 1000000]);
        $this->category = KategoriBarang::create(['nama_kategori' => 'Cincin']);
        $this->actingAs($this->admin);
    }

    private function data(array $extra = []): array
    {
        return array_merge(['branch_id' => $this->branch->id, 'nama_barang' => 'Cincin Uji', 'kategori_barang_id' => $this->category->id, 'kadar' => '24K', 'berat' => 1.125, 'ongkos' => 50000], $extra);
    }

    public function test_bulk_creation_produces_individual_unique_units_and_printable_barcodes(): void
    {
        $this->post(route('products.store'), $this->data(['jumlah' => 100, 'items' => array_fill(0, 100, $this->data())]))->assertSessionHasNoErrors()->assertRedirectContains('/products/labels?batch=');
        $products = Barang::all();
        $this->assertCount(100, $products);
        $this->assertCount(100, $products->pluck('kode_barang')->unique());
        $this->assertSame(['tersedia'], $products->pluck('status')->unique()->values()->all());
        $this->assertSame([$this->branch->id], $products->pluck('branch_id')->unique()->values()->all());
        $this->assertEquals(1175000, $products->first()->total_harga);
        $this->assertEquals(100, LogAudit::where('action', 'create_product')->count());
        $this->get(route('products.labels', ['batch' => $products->first()->creation_batch]))->assertOk()->assertViewHas('products', fn ($rows) => $rows->count() === 100)->assertSee($products->last()->kode_barang)->assertSee('window.print()', false);
        $svg = $this->get(route('products.barcode', $products->first()))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->getContent();
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($svg));
        $this->assertStringContainsString($products->first()->kode_barang, $svg);
        $this->get(route('products.barcode', ['product' => $products->first(), 'download' => 1]))->assertOk()->assertHeader('Content-Disposition', 'inline; filename="barcode-'.$products->first()->id.'.svg"');
        $this->post(route('products.store'), $this->data(['jumlah' => 1]))->assertSessionHasNoErrors();
        $this->assertEquals(101, Barang::distinct()->count('kode_barang'));
    }

    public function test_invalid_quantity_and_initial_status_create_no_products(): void
    {
        foreach ([0, -1, 1.5, 1001, 'abc'] as $quantity) {
            $this->post(route('products.store'), $this->data(['jumlah' => $quantity]))->assertSessionHasErrors('jumlah');
        }
        foreach (['terjual', 'service', 'arsip'] as $status) {
            $this->post(route('products.store'), $this->data(['status' => $status]))->assertSessionHasErrors('status');
        }
        $this->assertDatabaseCount('barang', 0);
    }

    public function test_failed_batch_rolls_back_units_and_cleans_shared_photo(): void
    {
        Storage::fake('public');
        $this->mock(AuditLogService::class, function ($mock) {
            $mock->shouldReceive('log')->once()->andReturnNull();
            $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        });
        $this->post(route('products.store'), $this->data(['jumlah' => 3, 'items' => array_fill(0, 3, $this->data()), 'photos' => [0 => UploadedFile::fake()->image('ring.png')]]))->assertStatus(500);
        $this->assertDatabaseCount('barang', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_lookup_validates_status_branch_duplicate_and_damaged_input(): void
    {
        $product = Barang::create($this->data(['kode_barang' => 'LEGACY-001', 'harga_per_gram' => 1000000, 'total_harga' => 1175000, 'status' => 'tersedia']));
        $query = ['barcode' => $product->kode_barang, 'branch_id' => $this->branch->id];
        $this->getJson(route('sales.barcode-query', $query))->assertOk()->assertJsonPath('id', $product->id)->assertJsonPath('branch_name', 'Cabang A')->assertJsonPath('harga_per_gram', '1000000.00');
        $this->getJson(route('sales.barcode-query', $query + ['selected_product_id' => $product->id]))->assertUnprocessable()->assertJsonPath('errors.barcode.0', 'Barang sudah dimasukkan ke nota yang sama.');
        foreach (['terjual' => 'Barang sudah terjual.', 'service' => 'Barang sedang dalam proses servis', 'arsip' => 'Barang tidak tersedia'] as $status => $message) {
            $product->update(['status' => $status]);
            $response = $this->getJson(route('sales.barcode-query', $query))->assertUnprocessable();
            $this->assertStringContainsString($message, $response->json('errors.barcode.0'));
        }
        foreach (['MISSING-CODE', "BAD\x01CODE", str_repeat('A', 256)] as $barcode) {
            $this->getJson(route('sales.barcode-query', array_merge($query, ['barcode' => $barcode])))->assertUnprocessable()->assertJsonValidationErrors('barcode');
        }
        $other = Cabang::create(['kode_cabang' => 'B', 'nama_cabang' => 'Cabang B']);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $other->id]));
        $this->getJson(route('sales.barcode-query', $query))->assertUnprocessable()->assertJsonPath('errors.barcode.0', 'Barang tidak tersedia di cabang kasir. Pilih barang dari cabang yang sesuai.');
        $this->get(route('products.barcode', $product))->assertForbidden();
        $this->get(route('products.labels', ['ids' => [$product->id]]))->assertNotFound();
        $this->post(route('products.store'), $this->data(['jumlah' => 2, 'items' => array_fill(0, 2, $this->data())]))->assertForbidden();
        $this->assertEquals(0, Barang::where('branch_id', $other->id)->count());
        $this->assertSame('LEGACY-001', $product->fresh()->kode_barang);
    }

    public function test_scanned_sale_uses_saved_price_prevents_reuse_and_cancellation_restores_stock(): void
    {
        $this->post(route('products.store'), $this->data())->assertSessionHasNoErrors();
        $product = Barang::first();
        $customer = Pelanggan::create(['nama' => 'Pelanggan', 'branch_id' => $this->branch->id]);
        $employee = Karyawan::create(['nama' => 'Siti']);
        $data = ['branch_id' => $this->branch->id, 'product_id' => $product->id, 'customer_id' => $customer->id, 'employee_name' => $employee->nama, 'barcode' => $product->kode_barang, 'harga_per_gram' => 1, 'ongkos' => 0];
        $this->post(route('sales.store'), array_merge($data, ['barcode' => 'WRONG']))->assertSessionHasErrors('barcode');
        $this->assertDatabaseCount('penjualan', 0);
        $this->post(route('sales.store'), $data)->assertSessionHasNoErrors();
        $sale = Penjualan::first();
        $this->assertEquals(1175000, $sale->total_harga);
        $this->assertSame('terjual', $product->fresh()->status);
        $this->assertEquals(0, Barang::where('branch_id', $this->branch->id)->where('status', 'tersedia')->count());
        $this->post(route('sales.store'), $data)->assertSessionHasErrors('barcode');
        $this->assertDatabaseCount('penjualan', 1);
        app(TransactionCancellationService::class)->cancel('sale', $sale->id, $this->admin->email, 'secret123', 'Batal sesuai permintaan customer');
        $this->assertSame('tersedia', $product->fresh()->status);
        $this->assertDatabaseHas('log_audit', ['action' => 'cancel_sale', 'record_id' => $sale->id]);
        $this->getJson(route('sales.barcode-query', ['branch_id' => $this->branch->id, 'barcode' => $product->kode_barang]))->assertOk();
        $this->get(route('reports.sales', ['status' => 'batal', 'print' => 1]))->assertOk()->assertSee($sale->nomor_nota)->assertSee('Batal sesuai permintaan customer')->assertViewHas('total', 0);
    }
}
