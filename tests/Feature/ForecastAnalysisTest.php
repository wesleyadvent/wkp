<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Models\Pengguna;
use App\Services\ArimaBatch;
use App\Services\DailySalesSeries;
use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ForecastAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_deduplicates_horizons_supports_long_lead_times_and_preserves_results_on_invalid_daily_data(): void
    {
        $end = CarbonImmutable::parse('2026-11-12', 'Asia/Jakarta');
        $this->travelTo($end->addDay()->setTime(12, 0));
        $branch = Cabang::create(['kode_cabang' => 'TEST', 'nama_cabang' => 'Cabang Uji']);
        $category = KategoriBarang::create(['nama_kategori' => 'Anting']);
        $group = ['key' => DailySalesSeries::key($branch->id, $category->id, '16K'), 'branch_id' => $branch->id, 'category_id' => $category->id, 'kategori' => 'Anting', 'kadar' => '16K', 'cutoff' => $end->toDateString(), 'input_hash' => str_repeat('1', 64), 'values' => array_fill(0, 180, 1)];
        $this->mock(DailySalesSeries::class)->shouldReceive('groups')->times(3)->andReturn([$group]);
        $configuration = KonfigurasiPersediaan::create(['branch_id' => $branch->id, 'kategori' => 'Anting', 'kadar' => '16K', 'method' => 'ARIMA', 'lead_time_hari' => 30, 'safety_stock' => 5]);
        $this->mock(ArimaBatch::class, function ($mock) {
            $mock->shouldReceive('run')->twice()->andReturnUsing(function ($groups) {
                $g = $groups[0];
                $this->assertSame(max($g['horizons']), $g['horizon']);
                $daily = range(1, $g['horizon']);

                return [$g['key'].'|'.$g['horizon'] => ['status' => 'ok', 'total' => array_sum($daily), 'daily' => $daily, 'history_days' => 180]];
            });
        });
        $args = ['--cutoff' => $end->toDateString(), '--branch' => $branch->id];
        $this->artisan('forecast:arima', $args)->assertSuccessful();
        $this->assertDatabaseCount('inventory_forecasts', 3);
        $configuration->update(['lead_time_hari' => 120]);
        $this->artisan('forecast:arima', $args)->assertSuccessful();
        $this->assertDatabaseCount('inventory_forecasts', 4);
        $this->assertDatabaseHas('inventory_forecasts', ['horizon' => 120, 'total' => 7260]);
        $this->assertDatabaseHas('inventory_forecasts', ['horizon' => 30, 'total' => 465]);

        $this->mock(ArimaBatch::class)->shouldReceive('run')->once()->andReturn([$group['key'].'|120' => ['status' => 'ok', 'total' => 1, 'daily' => [1]]]);
        $this->artisan('forecast:arima', $args)->assertFailed();
        $this->assertDatabaseCount('inventory_forecasts', 4);
        $this->assertDatabaseHas('inventory_forecasts', ['horizon' => 120, 'total' => 7260]);
    }

    public function test_unavailable_and_cached_arima_forecasts_remain_isolated_and_scoped(): void
    {
        $end = CarbonImmutable::parse('2026-03-28', 'Asia/Jakarta');
        $this->travelTo($end->addDay()->setTime(12, 0));
        $b = Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purwonegoro']);
        $c = KategoriBarang::create(['nama_kategori' => 'Cincin']);
        Barang::create(['kode_barang' => 'PWG-CIN-001', 'branch_id' => $b->id, 'kategori_barang_id' => $c->id, 'nama_barang' => 'Cincin', 'kadar' => '18K', 'berat' => 1, 'harga_per_gram' => 100000, 'total_harga' => 100000, 'status' => 'tersedia']);
        for ($i = 0; $i < 180; $i++) {
            DB::table('kalender_cabang')->insert(['branch_id' => $b->id, 'tanggal' => $end->subDays($i)->toDateString(), 'buka' => true, 'pencatatan_lengkap' => true]);
        }
        $service = app(InventoryAnalysisService::class);
        $pending = $service->analyze($b->id, 30, $end, 'ARIMA')->first();
        $this->assertNull($pending['metode_efektif']);
        $this->assertNull($pending['prediksi_unit']);
        $this->assertNull($pending['rop']);
        $this->assertNull($pending['jumlah_perlu_disiapkan']);
        $this->assertSame('prediksi_belum_tersedia', $pending['status_persediaan']);
        $g = array_values(app(DailySalesSeries::class)->groups($b->id, $end))[0];
        DB::table('inventory_forecasts')->insert(['branch_id' => $b->id, 'category_id' => $c->id, 'kadar' => '18K', 'cutoff' => $end->toDateString(), 'horizon' => 14, 'method' => 'ARIMA', 'version' => config('forecast.version'), 'input_hash' => $g['input_hash'], 'status' => 'ok', 'total' => 7.2, 'details' => json_encode(['history_days' => 180]), 'created_at' => now(), 'updated_at' => now()]);
        $row = $service->analyze($b->id, 30, $end, 'ARIMA')->first();
        $this->assertSame('ARIMA', $row['metode_efektif']);
        $this->assertSame(10, $row['rop']);
        $this->assertEquals(7.2, $row['prediksi_unit']);
        $this->assertNull($row['metode_efektif_periode']);
        $this->assertNull($row['prediksi_unit_periode']);
        $this->assertNotNull($row['alasan_fallback_periode']);
        DB::table('inventory_forecasts')->update(['horizon' => 21]);
        $this->assertNull($service->analyze($b->id, 30, $end, 'ARIMA')->first()['metode_efektif']);
        DB::table('inventory_forecasts')->update(['horizon' => 14, 'input_hash' => str_repeat('0', 64)]);
        $this->assertNull($service->analyze($b->id, 30, $end, 'ARIMA')->first()['metode_efektif']);
        $this->mock(ArimaBatch::class, function ($mock) {
            $mock->shouldReceive('run')->twice()->andReturnUsing(function ($groups) {
                $out = [];
                foreach ($groups as $g) {
                    $this->assertSame(90, $g['horizon']);
                    $daily = range(1, $g['horizon']);
                    $out[$g['key'].'|'.$g['horizon']] = ['status' => 'ok', 'total' => array_sum($daily), 'daily' => $daily, 'history_days' => 180];
                }

                return $out;
            });
        });
        $this->artisan('forecast:arima', ['--cutoff' => $end->toDateString(), '--branch' => $b->id])->assertSuccessful();
        $this->artisan('forecast:arima', ['--cutoff' => $end->toDateString(), '--branch' => $b->id])->assertSuccessful();
        $this->assertDatabaseCount('inventory_forecasts', 4);
        foreach ([30 => 465, 60 => 1830, 90 => 4095] as $period => $expected) {
            $row = $service->analyze($b->id, $period, $end, 'ARIMA')->first();
            $this->assertSame('ARIMA', $row['metode_efektif_periode']);
            $this->assertEquals($expected, $row['prediksi_unit_periode']);
            $this->assertEquals(105, $row['prediksi_unit']);
            $this->assertSame(107, $row['rop']);
            $this->assertNull($row['alasan_fallback_periode']);
        }
        DB::table('inventory_forecasts')->where('horizon', 30)->update(['input_hash' => str_repeat('0', 64)]);
        $row = $service->analyze($b->id, 30, $end, 'ARIMA')->first();
        $this->assertSame('ARIMA', $row['metode_efektif']);
        $this->assertNull($row['metode_efektif_periode']);
        $this->assertNull($row['prediksi_unit_periode']);
        $this->assertSame(107, $row['rop']);
        DB::table('kalender_cabang')->where('tanggal', $end->toDateString())->update(['pencatatan_lengkap' => null]);
        $row = $service->analyze($b->id, 30, $end, 'WMA')->first();
        $this->assertNull($row['rop']);
        $this->assertNull($row['prediksi_unit']);
        $this->assertNull($row['prediksi_unit_periode']);
        $other = Cabang::create(['kode_cabang' => 'MDR', 'nama_cabang' => 'Mandiraja']);
        $operator = Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $b->id]);
        $this->actingAs($operator)->get(route('inventory-analysis.index', ['metode' => 'ARIMA', 'branch_id' => $other->id, 'cutoff' => $end->toDateString()]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->getCollection()->every(fn ($r) => $r['branch_id'] === $b->id));
        $this->getJson(route('inventory-analysis.index', ['metode' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('inventory-analysis.index', ['cutoff' => now()->toDateString()]))->assertUnprocessable();
    }
}
