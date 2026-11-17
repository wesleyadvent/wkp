<?php

namespace Tests\Feature;

use App\Models\{Cabang, Pelanggan, Karyawan, HargaEmas, Barang, KategoriBarang, Pembelian, Penjualan, NotaLayanan, JenisLayanan, Pengguna};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Karyawan $employee;
    private Cabang $branch;
    private Pelanggan $customer;

    private KategoriBarang $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Cabang::create(['kode_cabang' => 'TEST', 'nama_cabang' => 'Cabang Uji']);
        $this->admin = Pengguna::factory()->create(['role' => 'admin']);
        $this->customer = Pelanggan::create(['nama' => 'Pelanggan Unik', 'telepon' => '081234567', 'alamat' => 'Alamat uji', 'branch_id' => $this->branch->id]);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 1000000]);
        $this->category = KategoriBarang::create(['nama_kategori' => 'Cincin']);
        $this->employee = Karyawan::create(['nama' => 'Karyawan Uji', 'is_active' => true]);
        $this->actingAs($this->admin);
    }

    private function product(array $overrides = []): Barang
    {
        return Barang::create(array_merge(['kode_barang' => 'EMAS-001', 'branch_id' => $this->branch->id, 'nama_barang' => 'Cincin Uji', 'kategori_barang_id' => $this->category->id, 'kadar' => '24K', 'berat' => 2, 'harga_per_gram' => 1000000, 'ongkos' => 50000, 'total_harga' => 2050000, 'status' => 'tersedia'], $overrides));
    }

    public function test_manual_status_restrictions_and_sale_transition(): void
    {
        $data = ['branch_id' => $this->branch->id, 'nama_barang' => 'Cincin', 'kategori_barang_id' => $this->category->id, 'kadar' => '24K', 'berat' => 2, 'ongkos' => 50000];
        foreach (['terjual', 'service'] as $status) {
            $this->post(route('products.store'), $data + ['status' => $status])->assertSessionHasErrors('status');
        }
        $this->post(route('products.store'), $data + ['status' => 'tersedia'])->assertSessionHasNoErrors();
        $product = $this->product();
        $this->put(route('products.update', $product), $data + ['status' => 'terjual'])->assertSessionHasErrors('status');
        $this->put(route('products.update', $product), $data + ['status' => 'service'])->assertSessionHasNoErrors();
        $this->assertSame('service', $product->fresh()->status);
        $this->put(route('products.update', $product), $data + ['status' => 'tersedia'])->assertSessionHasNoErrors();
        $this->post(route('sales.store'), ['employee_name' => $this->employee->nama, 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'product_id' => $product->id, 'tanggal' => today()->toDateString(), 'harga_per_gram' => 1, 'ongkos' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(2050000, Penjualan::latest('id')->first()->total_harga);
        $this->assertSame('terjual', $product->fresh()->status);
    }

    public function test_employee_is_required_active_and_historically_preserved(): void
    {
        foreach (['sales', 'purchases', 'service-notes'] as $route) {
            $this->get(route($route.'.create'))->assertOk()->assertSee('Nama karyawan pembuat nota');
            $this->post(route($route.'.store'), [])->assertSessionHasErrors('employee_name');
            $this->employee->update(['is_active' => false]);
            $this->post(route($route.'.store'), ['employee_name' => $this->employee->nama])->assertSessionHasErrors('employee_name');
            $this->employee->update(['is_active' => true]);
        }
        $base = ['employee_name' => $this->employee->nama, 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today()->toDateString()];
        $this->post(route('purchases.store'), $base + ['sumber_barang' => 'barang_luar', 'nama_barang' => 'Emas uji', 'berat' => 1, 'harga_per_gram_beli' => 1000000])->assertSessionHasNoErrors()->assertRedirect();
        $type = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 10000]);
        $this->post(route('service-notes.store'), $base + ['asal_barang' => 'barang_luar', 'nama_barang_manual' => 'Emas uji', 'service_type_ids' => [$type->id], 'service_prices' => [10000], 'status_service' => 'diterima', 'tanggal_masuk' => today()->toDateString()])->assertSessionHasNoErrors()->assertRedirect();
        $product = $this->product();
        $this->post(route('sales.store'), $base + ['product_id' => $product->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->employee->update(['nama' => 'Nama baru', 'is_active' => false]);
        foreach (['sales' => Penjualan::first(), 'purchases' => Pembelian::first(), 'service-notes' => NotaLayanan::first()] as $route => $note) {
            $this->assertSame('Karyawan Uji', $note->employee_name);
            $this->assertEquals($this->employee->id, $note->employee_id);
            $this->assertEquals($this->admin->id, $note->created_by);
            $this->get(route($route.'.show', $note))->assertOk()->assertSee('Karyawan Uji');
            $this->get(route($route.'.print', $note))->assertOk()->assertSee('Karyawan Uji');
        }
        $this->get(route('sales.show', Penjualan::first()))->assertDontSee('Terakhir diperbarui')->assertDontSee('<dt>Dibuat</dt>', false);
        $this->get(route('sales.create'))->assertDontSee('Ditambahkan')->assertDontSee('Diperbarui');
    }

    public function test_admin_can_manage_employees_and_branch_can_only_view(): void
    {
        $this->post(route('employees.store'), ['nama' => 'Siti', 'is_active' => 1])->assertRedirect(route('employees.index'));
        $employee = Karyawan::where('nama', 'Siti')->firstOrFail();
        $this->put(route('employees.update', $employee), ['nama' => 'Siti Uji', 'is_active' => 0])->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);
        $user = Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->branch->id]);
        $this->actingAs($user)->get(route('employees.index'))->assertOk()->assertSee('Siti Uji');
        $this->get(route('employees.create'))->assertForbidden();
        $this->post(route('employees.store'), ['nama' => 'Lain', 'is_active' => 1])->assertForbidden();
    }

    public function test_sale_code_lookup_and_purchase_multiple_deductions_use_database_item(): void
    {
        $product = $this->product(['status' => 'terjual']);
        $sale = Penjualan::create(['nomor_nota' => 'PJL/WKP-DSA/2026/0033', 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today(), 'status' => 'valid']);
        $sale->items()->create(['product_id' => $product->id, 'berat' => 2, 'harga_per_gram' => 1000000, 'ongkos' => 50000, 'subtotal' => 2050000]);
        foreach (['purchases.find-sale-query', 'service-notes.find-sale-query'] as $route) {
            $this->getJson(route($route, ['nomor_nota' => $sale->nomor_nota, 'branch_id' => $this->branch->id]))->assertOk()->assertJsonPath('items.0.product.nama_barang', 'Cincin Uji');
        }
        $first = \App\Models\JenisPotongan::create(['nama_potongan' => 'Cuci', 'nominal' => 5000, 'is_active' => true]);
        $second = \App\Models\JenisPotongan::create(['nama_potongan' => 'Rusak', 'nominal' => 10000, 'is_active' => true]);
        $data = ['employee_name' => '  karyawan uji  ', 'branch_id' => $this->branch->id, 'sumber_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota, 'nama_barang' => 'Nama palsu', 'berat' => 99, 'harga_per_gram_beli' => 900000, 'potongan_per_gram' => 10000, 'tanggal' => today()->toDateString(), 'deduction_type_ids' => [$first->id, $second->id], 'deduction_nominals' => [5000, 10000], 'deduction_notes' => ['Cuci ulang', 'Perbaikan']];
        $this->post(route('purchases.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $purchase = Pembelian::firstOrFail();
        $this->assertEquals($sale->id, $purchase->sale_id_ref);
        $this->assertEquals($this->customer->id, $purchase->customer_id);
        $this->assertSame('Cincin Uji', $purchase->items->first()->nama_barang);
        $this->assertEquals(2, $purchase->total_berat);
        $this->assertEquals(1765000, $purchase->total_harga);
        $this->assertCount(2, $purchase->deductions);
        $this->post(route('purchases.store'), $data)->assertSessionHasErrors('nomor_nota');
        $this->getJson(route('service-notes.find-sale-query', ['nomor_nota' => $sale->nomor_nota]))->assertUnprocessable();
    }

    public function test_service_from_sale_and_invalid_references_are_rejected(): void
    {
        $product = $this->product(['status' => 'terjual']);
        $sale = Penjualan::create(['nomor_nota' => 'PJL/WKP/2026/0001', 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today(), 'status' => 'valid']);
        $sale->items()->create(['product_id' => $product->id, 'berat' => 2, 'harga_per_gram' => 1000000, 'ongkos' => 50000, 'subtotal' => 2050000]);
        $type = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 10000]);
        $data = ['employee_name' => $this->employee->nama, 'branch_id' => $this->branch->id, 'asal_barang' => 'toko_sendiri', 'nomor_nota' => $sale->nomor_nota, 'service_type_ids' => [$type->id], 'service_prices' => [10000], 'status_service' => 'diterima', 'tanggal_masuk' => today()->toDateString()];
        $this->post(route('service-notes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $note = NotaLayanan::firstOrFail();
        $this->assertEquals($sale->id, $note->sale_id_ref);
        $this->assertEquals($product->id, $note->product_id_ref);
        $this->assertSame('Cincin Uji', $note->nama_barang_manual);
        $this->assertEquals(2, $note->berat);
        $this->post(route('service-notes.store'), array_replace($data, ['product_id_ref' => 999999]))->assertSessionHasErrors('nomor_nota');
        $this->post(route('service-notes.store'), array_replace($data, ['employee_name' => 'Orang luar']))->assertSessionHasErrors('employee_name');
        $sale->update(['status' => 'batal']);
        $this->post(route('service-notes.store'), $data)->assertSessionHasErrors('nomor_nota');
        $sale->update(['status' => 'valid']);
        $other = Cabang::create(['kode_cabang' => 'OTHER', 'nama_cabang' => 'Lain']);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $other->id]));
        $this->getJson(route('purchases.find-sale-query', ['nomor_nota' => $sale->nomor_nota]))->assertOk()->assertJsonPath('nomor_nota', $sale->nomor_nota);
        $this->getJson(route('service-notes.find-sale-query', ['nomor_nota' => $sale->nomor_nota]))->assertOk()->assertJsonPath('nomor_nota', $sale->nomor_nota);
    }

    public function test_employee_verification_controls_initial_form_lock(): void
    {
        foreach (['sales', 'purchases', 'service-notes'] as $route) {
            $response = $this->get(route($route.'.create'))->assertOk();
            $response->assertSee('id="note-fields" disabled', false)->assertSee(json_encode(route('employees.verify')), false);
            $response->assertDontSee('name="tanggal"', false)->assertDontSee('name="tanggal_masuk"', false);
            if ($route === 'service-notes') {
                $response->assertViewMissing('products');
            }
        }
        $this->postJson(route('employees.verify'), ['employee_name' => 'Orang luar'])->assertUnprocessable()->assertJsonValidationErrors('employee_name');
        $this->postJson(route('employees.verify'), ['employee_name' => '  karyawan UJI  '])->assertOk()->assertExactJson(['valid' => true]);
        $this->employee->update(['is_active' => false]);
        $this->postJson(route('employees.verify'), ['employee_name' => $this->employee->nama])->assertUnprocessable();
    }

    public function test_all_notes_use_current_jakarta_date_instead_of_submitted_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-11 01:30:00', 'Asia/Jakarta'));
        $product = $this->product();
        $base = ['employee_name' => $this->employee->nama, 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => '2001-01-01', 'tanggal_masuk' => '2001-01-01'];
        $this->post(route('sales.store'), $base + ['product_id' => $product->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('purchases.store'), $base + ['sumber_barang' => 'barang_luar', 'nama_barang' => 'Emas', 'berat' => 1, 'harga_per_gram_beli' => 1000000])->assertSessionHasNoErrors()->assertRedirect();
        $type = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 10000]);
        $this->post(route('service-notes.store'), $base + ['asal_barang' => 'barang_luar', 'nama_barang_manual' => 'Emas', 'service_type_ids' => [$type->id], 'service_prices' => [10000], 'status_service' => 'diterima'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2026-09-11', Penjualan::firstOrFail()->tanggal->toDateString());
        $this->assertSame('2026-09-11', Pembelian::firstOrFail()->tanggal->toDateString());
        $this->assertSame('2026-09-11', NotaLayanan::firstOrFail()->tanggal_masuk->toDateString());
        $this->travelBack();
    }

    public function test_photo_is_served_and_other_branch_is_forbidden(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('photo.png', 10, 'image/png')->store('product-photos', 'public');
        $product = $this->product(['photo_path' => $path]);
        $this->get(route('products.photo', $product))->assertOk();
        $other = Cabang::create(['kode_cabang' => 'OTHER', 'nama_cabang' => 'Lain']);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $other->id]));
        $this->get(route('products.photo', $product))->assertForbidden();
    }

    public function test_search_on_all_four_lists(): void
    {
        $base = ['nomor_nota' => 'NOTA-UNIK', 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today(), 'created_by' => $this->admin->id];
        Penjualan::create($base);
        Pembelian::create($base + ['sumber_barang' => 'barang_luar']);
        $type = JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 10000]);
        NotaLayanan::create($base + ['tanggal_masuk' => today(), 'asal_barang' => 'barang_luar', 'jenis_service_id' => $type->id, 'harga_service' => 10000, 'status_service' => 'diterima']);
        foreach (['sales', 'purchases', 'service-notes', 'customers'] as $route) {
            $this->get(route($route.'.index', ['q' => 'Pelanggan Unik']))->assertOk()->assertSee('Pelanggan Unik');
            $this->get(route($route.'.index', ['q' => 'TidakDitemukan']))->assertOk()->assertDontSee('Pelanggan Unik');
        }
    }

    public function test_details_and_print_include_items_and_deductions(): void
    {
        $product = $this->product(['photo_path' => 'product-photos/example.png']);
        $this->get(route('sales.create'))->assertOk()->assertSee('Rincian barang dari database')->assertSee('Pindai barcode barang');
        $sale = Penjualan::create(['nomor_nota' => 'PJL-TEST', 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today(), 'total_berat' => 2, 'total_ongkos' => 50000, 'total_harga' => 2050000, 'created_by' => $this->admin->id]);
        $sale->items()->create(['product_id' => $product->id, 'berat' => 2, 'harga_per_gram' => 1000000, 'ongkos' => 50000, 'subtotal' => 2050000]);
        $purchase = Pembelian::create(['nomor_nota' => 'PBL-TEST', 'branch_id' => $this->branch->id, 'customer_id' => $this->customer->id, 'tanggal' => today(), 'sumber_barang' => 'toko_sendiri', 'sale_id_ref' => $sale->id, 'total_berat' => 2, 'total_potongan' => 25000, 'total_harga' => 1975000]);
        $purchase->items()->create(['product_id_ref' => $product->id, 'nama_barang' => 'Cincin Uji', 'kategori' => 'Cincin', 'kadar' => '24K', 'berat' => 2, 'harga_per_gram_beli' => 1000000, 'potongan_per_gram' => 10000, 'subtotal' => 1980000]);
        $purchase->deductions()->create(['nominal' => 5000, 'keterangan' => 'Biaya pengujian']);
        foreach (['show', 'print'] as $action) {
            $this->get(route('sales.'.$action, $sale))->assertOk()->assertSee('Cincin Uji')->assertSee('2.050.000')->assertSee('Alamat uji')->assertSee(route('products.photo', $product));
            $this->get(route('purchases.'.$action, $purchase))->assertOk()->assertSee('Cincin Uji')->assertSee('Biaya pengujian')->assertSee('PJL-TEST')->assertSee('1.975.000');
        }
        $this->get(route('sales.print', $sale))->assertSee('window.print()', false);
        $this->get(route('purchases.print', $purchase))->assertSee('window.print()', false);
    }
}
