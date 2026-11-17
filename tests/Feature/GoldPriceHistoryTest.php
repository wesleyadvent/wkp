<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoldPriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_are_grouped_and_edits_preserve_history_and_update_stock(): void
    {
        $this->travelTo(now('Asia/Jakarta')->startOfDay()->setTime(12, 0));
        $admin = Pengguna::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $old = HargaEmas::create(['tanggal' => today()->subDay(), 'kadar' => '18K', 'harga_per_gram' => 2000000]);
        $branch = Cabang::create(['kode_cabang' => 'MDR', 'nama_cabang' => 'Mandiraja']);
        $category = KategoriBarang::create(['nama_kategori' => 'Anting', 'is_active' => true]);
        $product = Barang::create(['branch_id' => $branch->id, 'kategori_barang_id' => $category->id, 'nama_barang' => 'Anting', 'kadar' => '18K', 'berat' => 2, 'harga_per_gram' => 2000000, 'ongkos' => 50000, 'total_harga' => 4050000, 'status' => 'tersedia']);

        $this->put(route('gold-prices.update', $old), ['tanggal' => today()->toDateString(), 'kadar' => '18K', 'harga_per_gram' => 2500000])->assertSessionHasNoErrors();
        $current = HargaEmas::where('kadar', '18K')->latest('tanggal')->latest('id')->firstOrFail();
        $this->assertNotEquals($old->id, $current->id);
        $this->assertSame('2000000.00', $old->fresh()->harga_per_gram);
        $this->assertSame('5050000.00', $product->fresh()->total_harga);
        $this->get(route('gold-prices.index'))->assertOk()->assertSee('2.500.000,00')->assertDontSee('2.000.000,00')->assertViewHas('items', fn ($items) => $items->count() === 1);
        $this->get(route('gold-prices.show', $current))->assertOk()->assertSee('2.000.000,00')->assertSee('2.500.000,00');
        $this->put(route('gold-prices.update', $old), ['tanggal' => today()->toDateString(), 'kadar' => '18K', 'harga_per_gram' => 3000000])->assertSessionHasErrors('tanggal');
        $this->post(route('gold-prices.store'), ['tanggal' => today()->toDateString(), 'kadar' => '18k', 'harga_per_gram' => 3000000])->assertSessionHasErrors('kadar');
        $this->put(route('gold-prices.update', $current), ['tanggal' => today()->subDay()->toDateString(), 'kadar' => '18K', 'harga_per_gram' => 3000000])->assertSessionHasErrors('tanggal');
        $this->assertSame(2, HargaEmas::where('kadar', '18K')->count());
    }

    public function test_dataset_uses_only_the_price_known_by_forecast_date(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2023-01-05 12:00:00', 'Asia/Jakarta'));
        Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purwonegoro']);
        KategoriBarang::create(['nama_kategori' => 'Anting', 'is_active' => true]);
        HargaEmas::create(['tanggal' => '2023-01-01', 'kadar' => '18K', 'harga_per_gram' => 2000000]);
        HargaEmas::create(['tanggal' => '2023-01-03', 'kadar' => '18K', 'harga_per_gram' => 2500000]);
        foreach (range(1, 4) as $day) DB::table('harga_antam_harian')->insert(['tanggal' => '2023-01-0'.$day, 'harga' => 3000000, 'tanggal_sumber' => '2023-01-01', 'sumber' => 'uji']);
        $path = tempnam(sys_get_temp_dir(), 'wk-gold-test-');
        try {
            $this->artisan('data:build-forecast', ['--output' => $path])->assertSuccessful();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);
            $rows = [];
            while (($line = fgetcsv($file)) !== false) {
                $row = array_combine($header, $line);
                if ($row['kadar'] === '18K') $rows[$row['tanggal_ramalan']] = $row;
            }
            fclose($file);
            $this->assertEquals(2000000, $rows['2023-01-02']['harga_toko_per_gram']);
            $this->assertSame('2023-01-01', $rows['2023-01-02']['tanggal_harga_toko']);
            $this->assertEquals(2500000, $rows['2023-01-03']['harga_toko_per_gram']);
            $this->assertEquals(3000000, $rows['2023-01-03']['harga_antam']);
            $this->assertSame('', $rows['2023-01-03']['target_14']);
        } finally {
            unlink($path);
        }
    }
}
