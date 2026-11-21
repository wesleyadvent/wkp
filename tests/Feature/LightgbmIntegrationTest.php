<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Services\DailySalesSeries;
use App\Services\InventoryAnalysisService;
use App\Services\LightgbmFeatureBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightgbmIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Cabang $branch;

    private KategoriBarang $category;

    private CarbonImmutable $end;

    protected function setUp(): void
    {
        parent::setUp();
        $this->end = CarbonImmutable::parse('2026-03-14', 'Asia/Jakarta');
        $this->travelTo($this->end->addDay()->setTime(12, 0));
        $this->branch = Cabang::create(['kode_cabang' => 'DSA', 'nama_cabang' => 'Desa']);
        $this->category = KategoriBarang::create(['nama_kategori' => 'Anting']);
        foreach (range(1, 12) as $i) {
            Barang::create(['kode_barang' => 'LGB-'.$i, 'branch_id' => $this->branch->id, 'kategori_barang_id' => $this->category->id, 'nama_barang' => 'Anting', 'kadar' => '8K', 'berat' => 1, 'harga_per_gram' => 100000, 'total_harga' => 100000, 'status' => 'tersedia']);
        }
        for ($i = 0; $i < 180; $i++) {
            DB::table('kalender_cabang')->insert(['branch_id' => $this->branch->id, 'tanggal' => $this->end->subDays($i)->toDateString(), 'buka' => true, 'pencatatan_lengkap' => true]);
        }
    }

    private function response(array $request, float $units = 25.4): array
    {
        return array_merge(array_intersect_key($request, array_flip(['schema_version', 'model_version', 'as_of_date', 'horizon_days', 'forecast_use'])), [
            'predictions' => array_map(fn ($g) => ['group_key' => $g['group_key'], 'predicted_units' => $units], $request['groups']),
        ]);
    }

    private function fakeReadyService(callable $predict): void
    {
        Http::swap(new Factory);
        Http::fake(fn ($request) => str_ends_with($request->url(), '/health')
            ? Http::response(['ready' => true])
            : $predict($request));
    }

    public function test_offline_flask_shows_actionable_warning_without_predictions_or_rop(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']))
            ->get(route('inventory-analysis.lightgbm', ['cutoff' => $this->end->toDateString()]))
            ->assertOk()
            ->assertSee('Layanan Flask LightGBM belum menyala atau tidak dapat dihubungi.')
            ->assertSee('.\\.venv-lightgbm\\Scripts\\python.exe services\\lightgbm\\serve.py')
            ->assertDontSee('kelompok memiliki prediksi LightGBM yang belum tersedia.')
            ->assertDontSee('Connection refused')
            ->assertViewHas('rows', fn ($rows) => $rows->getCollection()->every(fn ($row) => $row['prediksi_unit'] === null && $row['prediksi_unit_periode'] === null
                && $row['rop'] === null && $row['jumlah_perlu_disiapkan'] === null));
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_flask_responding_with_unready_or_invalid_health_is_distinguished_from_offline(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        foreach ([[['ready' => false], 503], [['ready' => false], 200], [[], 200], ['invalid json', 200]] as [$body, $status]) {
            Http::swap(new Factory);
            Http::fake(['*' => Http::response($body, $status)]);
            $this->get(route('inventory-analysis.lightgbm', ['cutoff' => $this->end->toDateString()]))
                ->assertOk()->assertSee('Layanan Flask LightGBM merespons, tetapi model belum siap.')
                ->assertDontSee('Layanan Flask LightGBM belum menyala')
                ->assertDontSee('kelompok memiliki prediksi LightGBM yang belum tersedia.');
            Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        }
    }

    public function test_offline_warning_is_shown_even_when_no_groups_match_search(): void
    {
        Http::fake(fn () => throw new ConnectionException('Timeout'));
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']))
            ->get(route('inventory-analysis.lightgbm', ['cutoff' => $this->end->toDateString(), 'q' => 'tidak-ada']))
            ->assertOk()->assertViewHas('totalRows', 0)
            ->assertSee('Layanan Flask LightGBM belum menyala atau tidak dapat dihubungi.');
    }

    public function test_ready_flask_keeps_data_warnings_and_recovers_after_service_restart(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        $url = route('inventory-analysis.lightgbm', ['cutoff' => $this->end->toDateString()]);
        $this->get($url)->assertOk()->assertSee('Layanan Flask LightGBM belum menyala');

        $this->fakeReadyService(fn ($r) => Http::response($this->response($r->data())));
        $this->get($url)->assertOk()->assertDontSee('Layanan Flask LightGBM belum menyala')
            ->assertViewHas('summary', fn ($summary) => $summary['prediksi_belum_tersedia'] === 0);

        DB::table('kalender_cabang')->where('tanggal', $this->end->toDateString())->update(['pencatatan_lengkap' => null]);
        $this->get($url)->assertOk()->assertDontSee('Layanan Flask LightGBM belum menyala')
            ->assertSee('kelompok memiliki prediksi LightGBM yang belum tersedia.');
    }

    private function sale(CarbonImmutable $day, int $units, string $status = 'valid'): void
    {
        $customer = Pelanggan::firstOrCreate(['branch_id' => $this->branch->id, 'nama' => 'Fixture LightGBM']);
        $sale = DB::table('penjualan')->insertGetId(['nomor_nota' => 'FIXTURE-'.bin2hex(random_bytes(6)), 'branch_id' => $this->branch->id, 'customer_id' => $customer->id, 'tanggal' => $day->toDateString(), 'status' => $status]);
        for ($i = 0; $i < $units; $i++) {
            $product = Barang::create(['kode_barang' => 'FIX-'.bin2hex(random_bytes(6)), 'branch_id' => $this->branch->id, 'kategori_barang_id' => $this->category->id, 'nama_barang' => 'Anting', 'kadar' => '8K', 'berat' => 1, 'harga_per_gram' => 100000, 'total_harga' => 100000, 'status' => 'terjual']);
            DB::table('item_penjualan')->insert(['sale_id' => $sale, 'product_id' => $product->id, 'berat' => 1, 'harga_per_gram' => 100000, 'ongkos' => 0, 'subtotal' => 100000]);
        }
    }

    public function test_export_matches_request_features_and_targets_only_future_valid_sales(): void
    {
        $cutoff = CarbonImmutable::parse('2026-02-28', 'Asia/Jakarta');
        $this->sale($cutoff, 2);
        $this->sale($cutoff->addDay(), 3);
        $this->sale($cutoff->addDays(2), 4, 'batal');
        foreach (['2026-02-20' => 50, '2026-02-27' => 100, '2026-03-01' => 200] as $date => $price) {
            DB::table('harga_antam_harian')->insert(['tanggal' => $date, 'tanggal_sumber' => $date, 'harga' => $price, 'sumber' => 'fixture']);
        }
        $requests = [];
        $this->fakeReadyService(function ($r) use (&$requests) {
            $requests[$r['horizon_days']] = $r->data();

            return Http::response($this->response($r->data()));
        });
        app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $cutoff, 'LightGBM');
        $path = storage_path('app/private/lightgbm-export-test-'.bin2hex(random_bytes(6)).'.csv');
        try {
            $this->artisan('data:export-lightgbm', ['--from' => $cutoff->toDateString(), '--to' => $cutoff->toDateString(), '--branch' => $this->branch->id, '--output' => $path])->assertSuccessful();
            $handle = fopen($path, 'r');
            $header = fgetcsv($handle, escape: '');
            $row = array_combine($header, fgetcsv($handle, escape: ''));
            $this->assertFalse(fgetcsv($handle, escape: ''));
            fclose($handle);
            foreach ($requests[14]['groups'][0]['features'] as $name => $value) {
                $this->assertEquals($value, $row[$name], $name);
            }
            $this->assertEquals(3, $row['target_sales_14d']);
            $this->assertEquals(2, $row['sales_14d']);
            $this->assertEquals(100, $row['harga_antam']);
            $this->assertSame('2026-02-27', $row['tanggal_sumber_harga']);
            $this->assertEquals(100, $row['perubahan_harga_7_persen']);
            $this->assertNotContains('customer_id', $header);
            $this->assertNotContains('stok_tersedia', $header);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_export_supports_every_lead_time_model(): void
    {
        $cutoff = CarbonImmutable::parse('2026-02-14', 'Asia/Jakarta');
        $this->sale($cutoff, 2);
        $this->sale($cutoff->addDay(), 3);
        $path = storage_path('app/private/lightgbm-horizons-'.bin2hex(random_bytes(6)).'.csv');
        try {
            foreach ([3, 7, 14, 21] as $horizon) {
                $this->artisan('data:export-lightgbm', ['--from' => $cutoff->toDateString(), '--to' => $cutoff->toDateString(), '--horizon' => $horizon, '--branch' => $this->branch->id, '--output' => $path])->assertSuccessful();
                $handle = fopen($path, 'r');
                $header = fgetcsv($handle, escape: '');
                $row = array_combine($header, fgetcsv($handle, escape: ''));
                fclose($handle);
                $this->assertContains('open_days_next_'.$horizon, $header);
                $this->assertContains('pre_eid_days_next_'.$horizon, $header);
                $this->assertSame('', $row['feature_error']);
                $this->assertEquals(2, $row['sales_7d']);
                $this->assertEquals(3, $row['target_sales_'.$horizon.'d']);
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_live_flask_matches_colab_using_features_built_from_transactions(): void
    {
        if (! getenv('LIGHTGBM_LIVE_URL')) {
            $this->markTestSkipped('Set LIGHTGBM_LIVE_URL to run against the real Flask/PKL service.');
        }
        config(['forecast.lightgbm.url' => getenv('LIGHTGBM_LIVE_URL')]);
        $this->end = CarbonImmutable::parse('2025-10-01', 'Asia/Jakarta');
        $this->travelTo($this->end->addDay()->setTime(12, 0));
        for ($i = 0; $i < 180; $i++) {
            DB::table('kalender_cabang')->updateOrInsert(['branch_id' => $this->branch->id, 'tanggal' => $this->end->subDays($i)->toDateString()], ['buka' => true, 'pencatatan_lengkap' => true]);
        }
        foreach ([0 => 5, 7 => 7, 14 => 13, 30 => 27, 60 => 27] as $offset => $units) {
            $this->sale($this->end->subDays($offset), $units);
        }
        $fixtures = collect(json_decode(file_get_contents(base_path('services/lightgbm/fixtures/colab_lead_time.json')), true))->keyBy('input.lead_time_days');
        $builder = app(LightgbmFeatureBuilder::class);
        $g = array_values(app(DailySalesSeries::class)->groups($this->branch->id, $this->end))[0];
        foreach ([3, 7, 14, 21] as $horizon) {
            $fixture = $fixtures->get($horizon);
            KonfigurasiPersediaan::updateOrCreate(['method' => 'LightGBM', 'branch_id' => $this->branch->id, 'kategori' => 'Anting', 'kadar' => '8K'], ['lead_time_hari' => $horizon, 'safety_stock' => 2]);
            $features = $builder->build($g, $builder->metadata($horizon, 'lead_time'), $this->end, $builder->context([$g], $this->end, $horizon));
            $this->assertEquals($fixture['input']['features'], $features);
            $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
            $this->assertSame('LightGBM', $row['metode_efektif'], $row['alasan_fallback'] ?? '');
            $this->assertSame('LightGBM', $row['metode_efektif_periode']);
            $this->assertEqualsWithDelta($fixture['output']['predicted_units'], $row['prediksi_unit'], 1e-8);
            $this->assertSame((int) ceil($fixture['output']['predicted_units'] + 2), $row['rop']);
        }
    }

    public function test_predictions_cache_rop_and_selected_projection_use_the_correct_models(): void
    {
        $this->fakeReadyService(fn ($r) => Http::response($this->response($r->data(), $r['horizon_days'] === 14 ? 25.4 : 51.2)));
        $service = app(InventoryAnalysisService::class);
        $row = $service->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertSame('LightGBM', $row['metode_efektif']);
        $this->assertEquals(25.4, $row['prediksi_unit']);
        $this->assertEquals(51.2, $row['prediksi_unit_periode']);
        $this->assertSame(28, $row['rop']);
        $this->assertSame(16, $row['jumlah_perlu_disiapkan']);
        $this->assertSame('kritis', $row['status_persediaan']);
        $service->analyze($this->branch->id, 30, $this->end, 'LightGBM');
        Http::assertSentCount(4);
        $service->analyze($this->branch->id, 60, $this->end, 'LightGBM');
        Http::assertSentCount(6);
        $this->sale($this->end, 1);
        $service->analyze($this->branch->id, 30, $this->end, 'LightGBM');
        Http::assertSentCount(9);
        DB::table('kalender_cabang')->where('tanggal', $this->end->subDays(80)->toDateString())->update(['buka' => false]);
        $service->analyze($this->branch->id, 30, $this->end, 'LightGBM');
        // No numerical feature changed, so the cached predictions remain valid.
        Http::assertSentCount(10);

        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        $row = $service->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertNull($row['prediksi_unit']);
        $this->assertNull($row['rop']);
        $this->assertSame('unreachable', $service->lightgbmStatus()['state']);
    }

    public function test_wrong_identity_partial_duplicate_nonfinite_and_failed_responses_do_not_substitute_another_method(): void
    {
        foreach (['date', 'version', 'horizon', 'missing', 'duplicate', 'wrong_group', 'negative', 'string', 'unavailable', 'timeout'] as $failure) {
            Cache::flush();
            $this->fakeReadyService(function ($r) use ($failure) {
                if ($failure === 'timeout') {
                    throw new ConnectionException('Test timeout');
                }
                $data = $this->response($r->data());
                match ($failure) {
                    'date' => $data['as_of_date'] = '2026-03-01',
                    'version' => $data['model_version'] = 'wrong',
                    'horizon' => $data['horizon_days'] = 21,
                    'missing' => $data['predictions'] = [],
                    'duplicate' => $data['predictions'][] = $data['predictions'][0],
                    'wrong_group' => $data['predictions'][0]['group_key'] = 'someone-else',
                    'negative' => $data['predictions'][0]['predicted_units'] = -1,
                    'string' => $data['predictions'][0]['predicted_units'] = '25.4',
                    default => null,
                };

                return Http::response($data, $failure === 'unavailable' ? 503 : 200);
            });
            $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
            $this->assertNull($row['metode_efektif'], $failure);
            $this->assertNull($row['metode_efektif_periode'], $failure);
            $this->assertNotNull($row['alasan_fallback']);
            $this->assertNull($row['prediksi_unit']);
            $this->assertNull($row['prediksi_unit_periode']);
            $this->assertNull($row['rop']);
            $this->assertNull($row['jumlah_perlu_disiapkan']);
            $this->assertSame('prediksi_belum_tersedia', $row['status_persediaan']);
        }
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']))
            ->get(route('inventory-analysis.lightgbm', ['cutoff' => $this->end->toDateString()]))
            ->assertOk()->assertSee('Belum tersedia')->assertSee('Prediksi belum tersedia')
            ->assertDontSee('SMA/hari')->assertDontSee('fallback SMA')
            ->assertSee('Metode</dt><dd title="">LightGBM</dd>', false);
    }

    public function test_unsupported_lead_time_and_unknown_calendar_are_unavailable_independently(): void
    {
        $this->fakeReadyService(fn ($r) => Http::response($this->response($r->data(), 44.2)));
        $configuration = KonfigurasiPersediaan::create(['method' => 'LightGBM', 'branch_id' => $this->branch->id, 'kategori' => 'Anting', 'kadar' => '8K', 'lead_time_hari' => 20, 'safety_stock' => 5]);
        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertNull($row['metode_efektif']);
        $this->assertSame('LightGBM', $row['metode_efektif_periode']);
        $this->assertNull($row['rop']);
        $this->assertNull($row['jumlah_perlu_disiapkan']);
        Http::assertSentCount(2);
        $configuration->update(['lead_time_hari' => 30]);
        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertNull($row['metode_efektif']);
        $this->assertStringContainsString('belum dilatih', $row['alasan_fallback']);
        $g = array_values(app(DailySalesSeries::class)->groups($this->branch->id, $this->end))[0];
        $builder = app(LightgbmFeatureBuilder::class);
        $this->expectExceptionMessage('Kalender Idulfitri tahun 2028');
        $builder->build($g, $builder->metadata(90, 'projection'), CarbonImmutable::parse('2027-11-12', 'Asia/Jakarta'), $builder->context([$g], $this->end, 90));
    }

    public function test_feature_history_calendar_unknown_categories_and_branch_scope(): void
    {
        $builder = app(LightgbmFeatureBuilder::class);
        $g = array_values(app(DailySalesSeries::class)->groups($this->branch->id, $this->end))[0];
        $features = $builder->build($g, $builder->metadata(14, 'lead_time'), $this->end, $builder->context([$g], $this->end, 14));
        $this->assertSame(13, $features['open_days_next_14']);
        $this->assertSame(6, $features['pre_eid_days_next_14']);
        $this->assertNull($features['days_since_last_sale']);
        $this->fakeReadyService(fn ($r) => Http::response($this->response($r->data())));
        $other = Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purwonegoro']);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->branch->id]));
        $this->get(route('inventory-analysis.lightgbm', ['branch_id' => $other->id, 'cutoff' => $this->end->toDateString()]))->assertOk()->assertSee('Analisis Persediaan LightGBM');
        Http::assertSent(fn ($r) => $r->method() === 'POST' && collect($r['groups'])->every(fn ($g) => str_starts_with($g['group_key'], $this->branch->id.'|')));
        $this->get(route('inventory-settings.lightgbm.index'))->assertForbidden();
        DB::table('kalender_cabang')->where('tanggal', $this->end->toDateString())->update(['pencatatan_lengkap' => null]);
        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertNull($row['prediksi_unit']);
        $this->assertNull($row['prediksi_unit_periode']);
        $this->assertNull($row['rop']);
        $this->branch->update(['kode_cabang' => 'NEW']);
        DB::table('kalender_cabang')->where('tanggal', $this->end->toDateString())->update(['pencatatan_lengkap' => true]);
        $row = app(InventoryAnalysisService::class)->analyze($this->branch->id, 30, $this->end, 'LightGBM')->first();
        $this->assertNull($row['metode_efektif']);
        $this->assertStringContainsString('belum didukung', $row['alasan_fallback']);
    }
}
