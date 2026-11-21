<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Models\LogAudit;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Penjualan;
use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private Cabang $branch;

    private Cabang $otherBranch;

    private Pelanggan $customer;

    private int $productSequence = 1;

    private int $saleSequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Cabang::create(['kode_cabang' => 'BDG', 'nama_cabang' => 'Cabang Bandung']);
        $this->otherBranch = Cabang::create(['kode_cabang' => 'SBY', 'nama_cabang' => 'Cabang Surabaya']);
        $this->customer = Pelanggan::create(['nama' => 'Pelanggan Uji', 'branch_id' => $this->branch->id]);
        KategoriBarang::create(['nama_kategori' => 'Cincin']);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '70%', 'harga_per_gram' => 1000000]);
        foreach ([$this->branch, $this->otherBranch] as $branch) {
            for ($offset = 0; $offset <= 180; $offset++) {
                DB::table('kalender_cabang')->insert(['branch_id' => $branch->id, 'tanggal' => today()->subDays($offset)->toDateString(), 'buka' => true, 'pencatatan_lengkap' => true]);
            }
        }
    }

    public function test_valid_sales_available_stock_and_default_calculation_are_used(): void
    {
        foreach (range(1, 4) as $index) {
            $this->sale($this->branch, today()->subDays($index), 'valid');
        }
        foreach (range(1, 2) as $index) {
            $this->sale($this->branch, today()->subDays(30 + $index), 'valid');
        }
        foreach (range(1, 3) as $index) {
            $this->sale($this->branch, today()->subDays($index), 'batal');
        }

        $this->product($this->branch, 'tersedia');
        $this->product($this->branch, 'service');
        $this->product($this->branch, 'terjual');

        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, today())->first();

        $this->assertSame(1, $row['stok_tersedia']);
        $this->assertSame(4, $row['penjualan_periode_berjalan']);
        $this->assertSame(2, $row['penjualan_periode_sebelumnya']);
        $this->assertEqualsWithDelta(4 / 30, $row['rata_rata_penjualan_harian'], 0.00001);
        $this->assertEquals(100.0, $row['persentase_perubahan']);
        $this->assertSame('meningkat', $row['status_tren']);
        $this->assertSame(14, $row['lead_time_hari']);
        $this->assertEqualsWithDelta(4 / 30 * 14, $row['prediksi_unit'], 0.00001);
        $this->assertEqualsWithDelta(4, $row['prediksi_unit_periode'], 0.00001);
        $this->assertSame(2, $row['safety_stock']);
        $this->assertSame(4, $row['rop']);
        $this->assertSame(3, $row['jumlah_perlu_disiapkan']);
        $this->assertSame('kritis', $row['status_persediaan']);
    }

    public function test_configuration_overrides_defaults_and_new_trend_avoids_division_by_zero(): void
    {
        $this->sale($this->branch, today()->subDay(), 'valid');
        $this->product($this->branch, 'tersedia');
        KonfigurasiPersediaan::create([
            'branch_id' => $this->branch->id,
            'kategori' => 'Cincin',
            'kadar' => '70%',
            'lead_time_hari' => 10,
            'safety_stock' => 5,
        ]);

        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, today())->first();

        $this->assertNull($row['persentase_perubahan']);
        $this->assertSame('baru', $row['status_tren']);
        $this->assertSame(10, $row['lead_time_hari']);
        $this->assertSame(5, $row['safety_stock']);
        $this->assertSame(6, $row['rop']);
        $this->assertSame(5, $row['jumlah_perlu_disiapkan']);
    }

    public function test_both_forecasts_follow_their_own_horizon_for_every_selected_window(): void
    {
        $end = CarbonImmutable::instance(today()->subDay());
        $offsets = [0, 0, 14, 34, 64];
        foreach ($offsets as $offset) {
            $this->sale($this->branch, $end->subDays($offset), 'valid');
        }
        $this->product($this->branch, 'tersedia');
        $this->product($this->branch, 'tersedia');
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));

        foreach (['SMA', 'WMA'] as $method) {
            KonfigurasiPersediaan::create(['branch_id' => $this->branch->id, 'kategori' => 'Cincin', 'kadar' => '70%', 'method' => $method, 'lead_time_hari' => 20, 'safety_stock' => 5]);
            foreach ([30, 60, 90] as $period) {
                $inWindow = array_filter($offsets, fn ($offset) => $offset < $period);
                $daily = $method === 'SMA'
                    ? count($inWindow) / $period
                    : array_sum(array_map(fn ($offset) => $period - $offset, $inWindow)) / ($period * ($period + 1) / 2);
                $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, $period, $end, $method)->first();

                $this->assertEqualsWithDelta($daily * 20, $row['prediksi_unit'], 0.00001);
                $this->assertEqualsWithDelta($daily, $row['estimasi_harian_metode'], 0.00001);
                $this->assertEqualsWithDelta($daily * $period, $row['prediksi_unit_periode'], 0.00001);
                $this->assertSame($method, $row['metode_efektif_periode']);
                $this->assertSame((int) ceil($daily * 20 + 5), $row['rop']);
                $this->assertSame(max(0, $row['rop'] - 2), $row['jumlah_perlu_disiapkan']);
                $response = $this->get(route('inventory-analysis.'.strtolower($method), ['periode' => $period, 'cutoff' => $end->toDateString()]))
                    ->assertOk()
                    ->assertSee('Prediksi lead time (20 hari)')
                    ->assertSee('Prediksi jendela ('.$period.' hari)')
                    ->assertSee(number_format($daily * $period, 0, ',', '.').' unit');
                $response->assertSee($method.'/hari');
                if ($method === 'WMA') {
                    $response->assertDontSee('SMA/hari');
                }
            }
        }
    }

    public function test_branch_user_is_forced_to_own_branch_and_admin_can_filter_branch(): void
    {
        $this->product($this->branch, 'tersedia');
        $this->product($this->otherBranch, 'tersedia');
        $admin = Pengguna::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('inventory-analysis.index', ['branch_id' => $this->otherBranch->id, 'periode' => 60, 'per_page' => 10]))
            ->assertOk()
            ->assertViewHas('selectedBranchId', $this->otherBranch->id)
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->perPage() === 10)
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->getCollection()->every(fn (array $row) => $row['branch_id'] === $this->otherBranch->id));

        $branchUser = Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->branch->id]);
        $this->actingAs($branchUser)
            ->get(route('inventory-analysis.index', ['branch_id' => $this->otherBranch->id]))
            ->assertOk()
            ->assertViewHas('selectedBranchId', $this->branch->id)
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->getCollection()->every(fn (array $row) => $row['branch_id'] === $this->branch->id));

        $this->actingAs($admin)
            ->get(route('inventory-analysis.index', ['q' => 'Tidak ditemukan']))
            ->assertOk()
            ->assertViewHas('rows', fn (LengthAwarePaginator $rows) => $rows->total() === 0);
    }

    public function test_only_admin_can_save_inventory_configuration_and_changes_are_audited(): void
    {
        $data = [
            'branch_id' => $this->branch->id,
            'kategori' => 'Cincin',
            'kadar' => '70%',
            'lead_time_hari' => 21,
            'safety_stock' => 4,
        ];
        $branchUser = Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->branch->id]);
        $this->actingAs($branchUser)->get(route('inventory-settings.index'))->assertForbidden();
        $this->post(route('inventory-settings.store'), $data)->assertForbidden();

        $admin = Pengguna::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('inventory-settings.store'), $data)
            ->assertRedirect(route('inventory-settings.index', ['branch_id' => $this->branch->id]));

        $this->assertDatabaseHas('konfigurasi_persediaan', $data);
        $this->assertDatabaseHas('log_audit', ['action' => 'create_inventory_configuration', 'user_id' => $admin->id]);
        $this->assertSame(1, LogAudit::where('action', 'create_inventory_configuration')->count());
        $this->get(route('inventory-settings.index', ['branch_id' => $this->branch->id]))
            ->assertOk()
            ->assertViewHas('configurations', fn (LengthAwarePaginator $rows) => $rows->total() === 1);
        $this->get(route('inventory-settings.index', ['branch_id' => $this->branch->id, 'q' => 'Cincin']))
            ->assertOk()
            ->assertViewHas('configurations', fn (LengthAwarePaginator $rows) => $rows->total() === 1);
        $this->get(route('inventory-settings.index', ['branch_id' => $this->branch->id, 'q' => '70%']))
            ->assertOk()
            ->assertViewHas('configurations', fn (LengthAwarePaginator $rows) => $rows->total() === 1);
        $this->get(route('inventory-settings.index', ['branch_id' => $this->otherBranch->id]))
            ->assertOk()
            ->assertViewHas('configurations', fn (LengthAwarePaginator $rows) => $rows->getCollection()->contains(
                fn (array $row) => $row['lead_time_hari'] === 14 && $row['safety_stock'] === 2 && $row['menggunakan_default'],
            ));
        $this->get(route('inventory-settings.edit', [
            'branch_id' => $this->otherBranch->id,
            'kategori' => 'Cincin',
            'kadar' => '70%',
        ]))->assertOk()->assertSee('Edit Konfigurasi Persediaan');
    }

    private function sale(Cabang $branch, $date, string $status): Penjualan
    {
        $customer = $branch->is($this->branch)
            ? $this->customer
            : Pelanggan::firstOrCreate(['nama' => 'Pelanggan '.$branch->id, 'branch_id' => $branch->id]);
        $product = $this->product($branch, 'terjual');
        $sale = Penjualan::create([
            'nomor_nota' => 'PJL-UJI-'.str_pad((string) $this->saleSequence++, 4, '0', STR_PAD_LEFT),
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'tanggal' => $date,
            'status' => $status,
        ]);
        // Match MySQL DATE storage when these fixtures run on SQLite.
        DB::table('penjualan')->where('id', $sale->id)->update(['tanggal' => $date->toDateString()]);
        $sale->items()->create([
            'product_id' => $product->id,
            'berat' => 1,
            'harga_per_gram' => 1000000,
            'ongkos' => 0,
            'subtotal' => 1000000,
        ]);

        return $sale;
    }

    private function product(Cabang $branch, string $status): Barang
    {
        $sequence = $this->productSequence++;

        return Barang::create([
            'kode_barang' => 'ANL-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'branch_id' => $branch->id,
            'nama_barang' => 'Cincin Uji',
            'kategori_barang_id' => KategoriBarang::where('nama_kategori', 'Cincin')->valueOrFail('id'),
            'kadar' => '70%',
            'berat' => 1,
            'harga_per_gram' => 1000000,
            'ongkos' => 0,
            'total_harga' => 1000000,
            'status' => $status,
        ]);
    }
}
