<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Services\ForecastComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastComparisonTest extends TestCase
{
    use RefreshDatabase;

    private string $csv;

    private string $manifest;

    private Cabang $desa;

    private Cabang $mandiraja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->desa = Cabang::create(['kode_cabang' => 'DSA', 'nama_cabang' => 'Cabang Desa']);
        $this->mandiraja = Cabang::create(['kode_cabang' => 'MDR', 'nama_cabang' => 'Cabang Mandiraja']);
        $base = storage_path('app/private/comparison-fixture-'.bin2hex(random_bytes(6)));
        $this->csv = $base.'.csv';
        $this->manifest = $base.'.json';
        config(['forecast.comparison.file' => $this->csv, 'forecast.comparison.metadata' => $this->manifest]);
        $this->writeCases([
            [14, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-15', 10, 12, 11, 10, 10],
            [14, 'DSA', 'ANTING', 16, '2025-10-02', '2025-10-03', '2025-10-16', 0, 5, 0, 0, 0],
            [14, 'DSA', 'ANTING', 16, '2025-10-03', '2025-10-04', '2025-10-17', 20, 15, 21, 20, 20],
            [14, 'MDR', 'CINCIN', 18, '2025-10-01', '2025-10-02', '2025-10-15', 10, 10, 10, 20, 30],
            [30, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-31', 30, 33, 32, 31, 30],
            [3, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-04', 2, 2.6, 2.5, 2.4, 2.1],
            [7, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-08', 5, 6.1, 6.0, 5.9, 5.1],
            [21, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-22', 15, 16.5, 16.4, 16.3, 15.1],
        ]);
    }

    private function writeCases(array $rows): void
    {
        $file = fopen($this->csv, 'w');
        fputcsv($file, ForecastComparisonService::FIELDS, escape: '');
        foreach ($rows as $row) {
            fputcsv($file, $row, escape: '');
        }
        fclose($file);
        $horizons = [];
        foreach (ForecastComparisonService::HORIZONS as $horizon) {
            $horizons[$horizon] = ['cutoff_start' => '2025-10-01', 'cutoff_end' => '2026-07-17', 'selected' => ['SMA' => 'SMA60', 'WMA' => 'WMA90', 'ARIMA' => 'ARIMA(1, 0, 1)', 'LightGBM' => 'LightGBM_riwayat_jadwal_idulfitri']];
        }
        file_put_contents($this->manifest, json_encode(['schema_version' => 'forecast-comparison-v1', 'horizons' => $horizons, 'csv_sha256' => hash_file('sha256', $this->csv), 'total_rows' => count($rows), 'generated_at' => '2026-11-14T00:00:00Z']));
    }

    protected function tearDown(): void
    {
        foreach ([$this->csv, $this->manifest] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_metrics_are_paired_branch_filters_apply_and_tied_winners_share_first_place(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $this->get(route('forecast-comparison.index', ['branch_id' => $this->desa->id]))->assertOk()
            ->assertSee('Perbandingan Akurasi Ramalan')->assertSee('MAPE')->assertSee('Peringkat pertama bersama')
            ->assertViewHas('result', function ($result) {
                $sma = collect($result['rankings'])->firstWhere('method', 'SMA');
                $this->assertEqualsWithDelta(4, $sma['mae'], 1e-12);
                $this->assertEqualsWithDelta(sqrt(18), $sma['rmse'], 1e-12);
                $this->assertEqualsWithDelta(22.5, $sma['mape'], 1e-12);
                $this->assertSame(3, $result['count']);
                $this->assertSame(1, $result['zero_actual']);
                $this->assertSame(2, $result['mape_n']);
                $this->assertEqualsCanonicalizing(['ARIMA', 'LightGBM'], $result['winners']);
                $this->assertSame([1, 1, 3, 4], array_column($result['rankings'], 'rank'));

                return true;
            });
    }

    public function test_branch_user_cannot_expand_scope_using_query_filters(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $this->desa->id]));
        $this->get(route('forecast-comparison.index', ['branch_id' => $this->mandiraja->id, 'metric' => 'mape']))->assertOk()
            ->assertViewHas('selectedBranchId', $this->desa->id)
            ->assertViewHas('result', fn ($r) => $r['count'] === 3 && collect($r['details'])->every(fn ($row) => $row['branch'] === 'Cabang Desa'))
            ->assertDontSee('Cabang Mandiraja');
        $this->get(route('forecast-comparison.index', ['q' => 'Mandiraja']))->assertOk()->assertViewHas('result', fn ($r) => $r['count'] === 0);
    }

    public function test_horizon_dates_category_karat_and_pagination_use_the_same_filtered_cases(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $this->get(route('forecast-comparison.index', ['horizon' => 30, 'metric' => 'rmse', 'category' => 'ANTING', 'karat' => 16]))
            ->assertOk()->assertViewHas('result', fn ($r) => $r['count'] === 1 && $r['winners'] === ['LightGBM']);
        $this->get(route('forecast-comparison.index', ['branch_id' => $this->desa->id, 'from' => '2025-10-02', 'to' => '2025-10-03']))
            ->assertOk()->assertViewHas('result', fn ($r) => $r['count'] === 2 && $r['date_count'] === 2);
        $this->get(route('forecast-comparison.index', ['per_page' => 10, 'page' => 2]))->assertOk()
            ->assertViewHas('details', fn ($p) => $p->total() === 4 && $p->count() === 0);
        $this->getJson(route('forecast-comparison.index', ['horizon' => 20]))->assertUnprocessable();
        $this->getJson(route('forecast-comparison.index', ['metric' => 'accuracy']))->assertUnprocessable();
        $this->getJson(route('forecast-comparison.index', ['from' => '2026-01-01', 'to' => '2025-01-01']))->assertUnprocessable();
    }

    public function test_mape_with_all_zero_actuals_and_empty_filters_do_not_name_a_winner(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $this->get(route('forecast-comparison.index', ['metric' => 'mape', 'from' => '2025-10-02', 'to' => '2025-10-02']))
            ->assertOk()->assertSee('Peringkat MAPE belum dapat ditentukan')
            ->assertViewHas('result', fn ($r) => $r['best']['mape'] === null && $r['winners'] === [] && $r['zero_actual'] === 1);
        $this->get(route('forecast-comparison.index', ['q' => 'Tidak ditemukan']))->assertOk()->assertSee('Belum ada observasi uji yang cocok');
    }

    public function test_new_lead_time_filters_show_paired_predictions_and_metrics(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        foreach ([3 => 2, 7 => 5, 21 => 15] as $horizon => $actual) {
            $this->get(route('forecast-comparison.index', ['horizon' => $horizon]))
                ->assertOk()
                ->assertSee($horizon.' hari · Lead time')
                ->assertViewHas('filters', fn ($filters) => $filters['horizon'] === $horizon)
                ->assertViewHas('result', function ($result) use ($actual) {
                    $this->assertSame(1, $result['count']);
                    $this->assertEquals($actual, $result['details'][0]['actual']);
                    $this->assertEqualsWithDelta(0.1, collect($result['rankings'])->firstWhere('method', 'LightGBM')['mae'], 1e-12);
                    $this->assertSame(['LightGBM'], $result['winners']);
                    $this->assertCount(4, $result['details'][0]['predictions']);

                    return true;
                });
        }
    }

    public function test_incomplete_observation_is_excluded_for_all_methods_and_integrity_is_verified(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $this->writeCases([[14, 'DSA', 'ANTING', 16, '2025-10-01', '2025-10-02', '2025-10-15', 10, 12, '', 10, 10]]);
        $this->get(route('forecast-comparison.index'))->assertOk()->assertViewHas('result', fn ($r) => $r['count'] === 0 && $r['excluded'] === 1 && collect($r['rankings'])->every(fn ($row) => $row['n'] === 0));
        file_put_contents($this->csv, 'corrupt', FILE_APPEND);
        $this->get(route('forecast-comparison.index'))->assertOk()->assertSee('Berkas evaluasi belum lengkap atau berubah');
        unlink($this->manifest);
        $this->get(route('forecast-comparison.index'))->assertOk()->assertSee('Hasil evaluasi belum tersedia');
    }

    public function test_comparison_requires_login(): void
    {
        $this->get(route('forecast-comparison.index'))->assertRedirect(route('login'));
    }
}
