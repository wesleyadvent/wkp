<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Models\Pengguna;
use App\Services\ArimaBatch;
use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeparateForecastPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_and_settings_are_isolated_and_route_controls_method(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-14 12:00', 'Asia/Jakarta'));
        $branch = Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purwonegoro']);
        $category = KategoriBarang::create(['nama_kategori' => 'Cincin', 'is_active' => true]);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '18K', 'harga_per_gram' => 1000000]);
        Barang::create(['branch_id' => $branch->id, 'kategori_barang_id' => $category->id, 'nama_barang' => 'Cincin', 'kadar' => '18K', 'berat' => 1, 'harga_per_gram' => 1000000, 'total_harga' => 1000000, 'status' => 'tersedia']);
        for ($i = 1; $i <= 180; $i++) {
            DB::table('kalender_cabang')->insert(['branch_id' => $branch->id, 'tanggal' => today()->subDays($i)->toDateString(), 'buka' => true, 'pencatatan_lengkap' => true]);
        }
        $base = ['branch_id' => $branch->id, 'kategori' => 'Cincin', 'kadar' => '18K'];
        foreach (['SMA', 'WMA', 'ARIMA'] as $method) {
            KonfigurasiPersediaan::create($base + ['method' => $method, 'lead_time_hari' => 14, 'safety_stock' => 2]);
        }
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        foreach (['sma' => 'SMA', 'wma' => 'WMA', 'arima' => 'ARIMA'] as $slug => $method) {
            $response = $this->get(route('inventory-analysis.'.$slug, ['metode' => 'WMA']))->assertOk()->assertViewHas('metode', $method)->assertDontSee('<select id="metode"', false)->assertSee('Konfigurasi '.$method)
                ->assertViewHas('rows', fn ($rows) => $rows->getCollection()->every(fn ($r) => ($r['metode_efektif'] === $method || $r['metode_efektif'] === null) && ($r['metode_efektif_periode'] === $method || $r['metode_efektif_periode'] === null)));
            if ($method !== 'SMA') {
                $response->assertDontSee('SMA/hari')->assertDontSee('fallback SMA');
            }
            if ($method === 'ARIMA') {
                $response->assertSee('Belum tersedia')->assertSee('Prediksi belum tersedia')
                    ->assertViewHas('summary', fn ($summary) => $summary['prediksi_belum_tersedia'] === 1 && $summary['belum_lengkap'] === 0);
            }
            $this->get(route('inventory-settings.'.$slug.'.index'))->assertOk()->assertViewHas('metode', $method);
            $this->get(route('inventory-settings.'.$slug.'.edit', $base))->assertOk()->assertSee(route('inventory-settings.'.$slug.'.store'), false);
        }
        $this->post(route('inventory-settings.wma.store'), $base + ['method' => 'SMA', 'lead_time_hari' => 8, 'safety_stock' => 9])->assertRedirect(route('inventory-settings.wma.index', ['branch_id' => $branch->id]));
        $this->assertDatabaseHas('konfigurasi_persediaan', $base + ['method' => 'WMA', 'lead_time_hari' => 8, 'safety_stock' => 9]);
        $this->assertDatabaseHas('konfigurasi_persediaan', $base + ['method' => 'SMA', 'lead_time_hari' => 14, 'safety_stock' => 2]);
        $this->assertDatabaseHas('konfigurasi_persediaan', $base + ['method' => 'ARIMA', 'lead_time_hari' => 14, 'safety_stock' => 2]);
        $end = today()->subDay();
        $service = app(InventoryAnalysisService::class);
        $this->assertSame(9, $service->analyze($branch->id, 30, $end, 'WMA')->first()['rop']);
        $this->assertSame(2, $service->analyze($branch->id, 30, $end, 'SMA')->first()['rop']);
        $this->mock(ArimaBatch::class, function ($mock) {
            $mock->shouldReceive('run')->once()->andReturnUsing(function ($groups) {
                $results = [];
                foreach ($groups as $g) {
                    $this->assertSame(90, $g['horizon']);
                    $results[$g['key'].'|90'] = ['status' => 'unavailable', 'total' => null, 'reason' => 'uji'];
                }

                return $results;
            });
        });
        $this->artisan('forecast:arima', ['--cutoff' => $end->toDateString(), '--branch' => $branch->id])->assertSuccessful();
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]));
        foreach (['sma', 'wma', 'arima'] as $slug) {
            $this->get(route('inventory-analysis.'.$slug))->assertOk();
            $this->get(route('inventory-settings.'.$slug.'.index'))->assertForbidden();
            $this->post(route('inventory-settings.'.$slug.'.store'), $base + ['lead_time_hari' => 99, 'safety_stock' => 99])->assertForbidden();
        }
    }
}
