<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Models\Pengguna;
use App\Services\ArimaBatch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomaticArimaTest extends TestCase
{
    use RefreshDatabase;

    private Cabang $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-19 12:00', 'Asia/Jakarta'));
        $this->branch = $this->stockGroup('PWG');
        HargaEmas::create(['tanggal' => today(), 'kadar' => '18K', 'harga_per_gram' => 1000000]);
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
    }

    private function stockGroup(string $code): Cabang
    {
        $branch = Cabang::create(['kode_cabang' => $code, 'nama_cabang' => $code]);
        $category = KategoriBarang::firstOrCreate(['nama_kategori' => 'Cincin']);
        Barang::create(['branch_id' => $branch->id, 'kategori_barang_id' => $category->id, 'nama_barang' => 'Cincin', 'kadar' => '18K', 'berat' => 1, 'harga_per_gram' => 1000000, 'total_harga' => 1000000, 'status' => 'tersedia']);

        return $branch;
    }

    private function mockForecasts(int $calls = 1, string $status = 'ok'): void
    {
        $this->mock(ArimaBatch::class)->shouldReceive('run')->times($calls)->andReturnUsing(function ($groups) use ($status) {
            $this->assertCount(1, $groups);
            $group = $groups[0];

            return [$group['key'].'|'.$group['horizon'] => [
                'status' => $status, 'total' => $status === 'ok' ? $group['horizon'] : null,
                'daily' => array_fill(0, $group['horizon'], 1), 'history_days' => 180, 'reason' => $status === 'ok' ? null : 'Riwayat belum cukup.',
            ]];
        });
    }

    private function prepare(string $cutoff = '2026-11-12', ?int $branch = null)
    {
        return $this->postJson(route('inventory-analysis.arima.prepare'), ['cutoff' => $cutoff, 'branch_id' => $branch]);
    }

    public function test_new_date_is_prepared_and_cached_for_all_windows_without_recomputing(): void
    {
        $this->get(route('inventory-analysis.arima', ['cutoff' => '2026-11-12']))->assertOk()
            ->assertViewHas('arimaPending', 1)->assertSee('data-arima-prepare', false);
        $this->mockForecasts(2);
        $this->prepare()->assertOk()->assertJson(['complete' => true, 'remaining' => 0]);
        $this->assertDatabaseCount('inventory_forecasts', 4);
        foreach ([14, 30, 60, 90] as $horizon) {
            $this->assertDatabaseHas('inventory_forecasts', ['cutoff' => '2026-11-12', 'horizon' => $horizon, 'total' => $horizon]);
        }
        $this->prepare()->assertOk()->assertJson(['complete' => true]);
        $this->get(route('inventory-analysis.arima', ['cutoff' => '2026-11-12']))->assertOk()
            ->assertViewHas('arimaPending', 0)->assertDontSee('data-arima-prepare', false)
            ->assertViewHas('rows', fn ($rows) => $rows->first()['prediksi_unit'] === 14.0);
        $this->prepare('2026-11-11')->assertOk()->assertJson(['complete' => true]);
        $this->assertDatabaseCount('inventory_forecasts', 8);
    }

    public function test_history_horizon_and_version_changes_trigger_recalculation(): void
    {
        $this->mockForecasts(5);
        $this->prepare()->assertOk();
        DB::table('kalender_cabang')->insert(['branch_id' => $this->branch->id, 'tanggal' => '2026-11-12', 'buka' => false, 'pencatatan_lengkap' => true]);
        $this->prepare()->assertOk();
        KonfigurasiPersediaan::create(['branch_id' => $this->branch->id, 'kategori' => 'Cincin', 'kadar' => '18K', 'method' => 'ARIMA', 'lead_time_hari' => 21, 'safety_stock' => 2]);
        $this->prepare()->assertOk();
        $this->assertDatabaseHas('inventory_forecasts', ['horizon' => 21, 'total' => 21]);
        DB::table('inventory_forecasts')->where('horizon', 60)->delete();
        $this->prepare()->assertOk();
        config(['forecast.version' => 'new-version']);
        $this->prepare()->assertOk();
        $this->assertDatabaseHas('inventory_forecasts', ['version' => 'new-version', 'horizon' => 21]);
    }

    public function test_requests_are_incremental_and_branch_users_cannot_prepare_another_branch(): void
    {
        $other = $this->stockGroup('MDR');
        $this->mockForecasts(2);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->branch->id]));
        $this->prepare(branch: $other->id)->assertOk()->assertJson(['complete' => true]);
        $this->assertDatabaseMissing('inventory_forecasts', ['branch_id' => $other->id]);
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $this->prepare()->assertOk()->assertJson(['complete' => true]);
        $this->assertDatabaseHas('inventory_forecasts', ['branch_id' => $other->id]);
    }

    public function test_multiple_groups_are_processed_one_at_a_time_and_unavailable_results_are_not_retried_forever(): void
    {
        $this->stockGroup('MDR');
        $this->mockForecasts(2, 'unavailable');
        $this->prepare()->assertOk()->assertJson(['complete' => false, 'remaining' => 1]);
        $this->assertDatabaseCount('inventory_forecasts', 4);
        $this->prepare()->assertOk()->assertJson(['complete' => true, 'remaining' => 0]);
        $this->prepare()->assertOk()->assertJson(['complete' => true]);
        $this->get(route('inventory-analysis.arima', ['cutoff' => '2026-11-12']))->assertOk()
            ->assertViewHas('arimaPending', 0)->assertSee('Riwayat belum cukup.');
    }

    public function test_overlapping_requests_wait_and_failures_preserve_saved_results_and_release_lock(): void
    {
        $lock = Cache::lock('arima-prepare:2026-11-12', 60);
        $this->assertTrue($lock->get());
        $this->prepare()->assertOk()->assertJson(['complete' => false, 'busy' => true]);
        $lock->release();
        $this->mockForecasts();
        $this->prepare()->assertOk();
        DB::table('inventory_forecasts')->where('horizon', 30)->update(['input_hash' => str_repeat('0', 64)]);
        $this->mock(ArimaBatch::class)->shouldReceive('run')->once()->andThrow(new \RuntimeException('private details'));
        $this->prepare()->assertStatus(503)->assertJsonPath('message', 'Perhitungan ARIMA terhenti. Muat ulang halaman untuk mencoba lagi. Jika tetap gagal, hubungi administrator.')
            ->assertDontSee('private details');
        $this->assertDatabaseCount('inventory_forecasts', 4);
        $this->assertDatabaseHas('inventory_forecasts', ['horizon' => 30, 'total' => 30]);
        $this->mockForecasts();
        $this->prepare()->assertOk()->assertJson(['complete' => true]);
    }

    public function test_endpoint_requires_authentication_and_a_past_date(): void
    {
        $this->prepare('2026-11-19')->assertUnprocessable();
        $this->prepare('invalid')->assertUnprocessable();
        $this->prepare(branch: 999)->assertUnprocessable();
        auth()->logout();
        $this->prepare()->assertUnauthorized();
    }
}
