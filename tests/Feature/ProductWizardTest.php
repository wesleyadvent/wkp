<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\HargaEmas;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWizardTest extends TestCase
{
    use RefreshDatabase;

    private KategoriBarang $category;

    private function item(int $number = 1): array
    {
        return ['nama_barang' => 'Barang '.$number, 'kategori_barang_id' => $this->category->id, 'kadar' => '24K', 'berat' => $number, 'ongkos' => 1000 * $number, 'deskripsi' => 'Rincian '.$number];
    }

    private function setupData(): Cabang
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 1000000]);
        $this->category = KategoriBarang::create(['nama_kategori' => 'Cincin']);

        return Cabang::create(['kode_cabang' => 'A', 'nama_cabang' => 'Cabang A']);
    }

    public function test_ten_independent_items_redirect_to_all_labels(): void
    {
        $this->travelTo(Carbon::parse('2026-09-12 09:00:00', 'Asia/Jakarta'));
        $branch = $this->setupData();
        $items = array_map(fn ($number) => $this->item($number), range(1, 10));
        $response = $this->post(route('products.store'), ['jumlah' => 10, 'branch_id' => $branch->id, 'items_json' => json_encode($items)])->assertSessionHasNoErrors();
        $products = Barang::orderBy('id')->get();
        $this->assertCount(10, $products);
        foreach ($products as $index => $product) {
            $this->assertSame('Barang '.($index + 1), $product->nama_barang);
            $this->assertEquals(($index + 1) * 1001000, $product->total_harga);
            $this->assertSame('A-CIN-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $product->kode_barang);
        }
        $response->assertRedirect(route('products.labels', ['batch' => $products->first()->creation_batch]));
        $this->get($response->headers->get('Location'))->assertOk()->assertViewHas('products', fn ($rows) => $rows->count() === 10)->assertSee('Barang 10')->assertSee('Cetak tag barcode');
        $this->get(route('products.index', ['branch_id' => $branch->id]))->assertOk()->assertDontSee('type="checkbox"', false)->assertDontSee('Cetak label barang terpilih');
        $this->get(route('products.create'))->assertOk()->assertSee('wizard-editor')->assertSee('Selanjutnya')->assertSee('Sebelumnya');
    }

    public function test_invalid_or_incomplete_steps_do_not_save_partial_products(): void
    {
        $branch = $this->setupData();
        $base = ['jumlah' => 2, 'branch_id' => $branch->id];
        $this->post(route('products.store'), $base + ['items' => [$this->item()]])->assertSessionHasErrors('jumlah');
        $this->postJson(route('products.store'), $base + ['items_json' => json_encode([$this->item(), array_merge($this->item(2), ['berat' => 0])])])->assertUnprocessable()->assertJsonValidationErrors('items.1.berat');
        $this->postJson(route('products.store'), $base + ['items_json' => 'null'])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('barang', 0);
    }

    public function test_branch_category_sequence_preserves_legacy_codes_and_never_reuses_deleted_codes(): void
    {
        $this->travelTo(Carbon::parse('2026-09-12 09:00:00', 'Asia/Jakarta'));
        $branch = $this->setupData();
        $other = Cabang::create(['kode_cabang' => 'B', 'nama_cabang' => 'Cabang B']);
        $legacy = Barang::create($this->item() + ['kode_barang' => 'CINCIN-OLD', 'branch_id' => $branch->id, 'harga_per_gram' => 1000000, 'total_harga' => 1001000]);
        $this->post(route('products.store'), ['jumlah' => 1, 'branch_id' => $branch->id, 'items' => [$this->item()]])->assertSessionHasNoErrors();
        $product = Barang::latest('id')->first();
        $this->assertSame('A-CIN-001', $product->kode_barang);
        $product->forceDelete();
        $this->post(route('products.store'), ['jumlah' => 1, 'branch_id' => $branch->id, 'items' => [$this->item()]])->assertSessionHasNoErrors();
        $this->assertSame('A-CIN-002', Barang::latest('id')->first()->kode_barang);
        $this->post(route('products.store'), ['jumlah' => 1, 'branch_id' => $other->id, 'items' => [$this->item()]])->assertSessionHasNoErrors();
        $this->assertSame('B-CIN-001', Barang::latest('id')->first()->kode_barang);
        $this->travelTo(Carbon::parse('2026-09-13 00:01:00', 'Asia/Jakarta'));
        $this->post(route('products.store'), ['jumlah' => 1, 'branch_id' => $branch->id, 'items' => [$this->item()]])->assertSessionHasNoErrors();
        $this->assertSame('A-CIN-003', Barang::latest('id')->first()->kode_barang);
        $this->assertSame('CINCIN-OLD', $legacy->fresh()->kode_barang);
    }

    public function test_only_admin_can_add_and_edit_products_while_branch_can_print_labels(): void
    {
        $branch = $this->setupData();
        $this->post(route('products.store'), ['jumlah' => 1, 'branch_id' => $branch->id, 'items' => [$this->item()]])->assertSessionHasNoErrors();
        $product = Barang::firstOrFail();
        $this->get(route('products.edit', $product))->assertOk();
        $this->get(route('products.index', ['branch_id' => $branch->id]))->assertOk()->assertSee('Tambah Barang')->assertSee('>Label</a>', false)->assertSee('>Edit</a>', false)->assertDontSee('Unduh SVG');
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]));
        $this->get(route('products.index'))->assertOk()->assertDontSee('Tambah Barang')->assertDontSee('>Edit</a>', false)->assertSee('>Label</a>', false);
        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), ['jumlah' => 1, 'items' => [$this->item()]])->assertForbidden();
        $this->get(route('products.edit', $product))->assertForbidden();
        $this->put(route('products.update', $product), $this->item(2))->assertForbidden();
        $this->patch(route('products.update', $product), $this->item(2))->assertForbidden();
        $this->get(route('products.labels', ['ids' => [$product->id]]))->assertOk()->assertSee('Pratinjau tag')->assertSee('window.print()', false);
        $this->assertDatabaseCount('barang', 1);
        $this->assertSame('Barang 1', $product->fresh()->nama_barang);
    }

    public function test_customer_search_matches_name_and_phone_across_branches_and_respects_limit(): void
    {
        $branch = $this->setupData();
        $other = Cabang::create(['kode_cabang' => 'B', 'nama_cabang' => 'Cabang B']);
        for ($number = 1; $number <= 25; $number++) {
            Pelanggan::create(['nama' => 'Siti '.$number, 'telepon' => '08123-'.$number, 'branch_id' => $branch->id]);
        }
        Pelanggan::create(['nama' => 'Siti Rahasia', 'telepon' => '089999', 'branch_id' => $other->id]);
        $this->getJson(route('sales.customer-query', ['branch_id' => $branch->id, 'q' => 'Siti']))->assertOk()->assertJsonCount(20);
        $this->getJson(route('sales.customer-query', ['branch_id' => $branch->id, 'q' => '08123-25']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.nama', 'Siti 25');
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]));
        $this->getJson(route('sales.customer-query', ['branch_id' => $other->id, 'q' => 'Siti Rahasia']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.nama', 'Siti Rahasia');
        $this->get(route('sales.create'))->assertOk()->assertSee('Ketik nama atau nomor telepon')->assertSee('type="hidden" name="product_id"', false)->assertDontSee('<select name="product_id"', false)->assertDontSee('Barang tersedia');
    }
}
