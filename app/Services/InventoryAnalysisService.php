<?php

namespace App\Services;

use App\Models\KonfigurasiPersediaan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryAnalysisService
{
    public const PERIODS = [30, 60, 90];

    public const DEFAULT_LEAD_TIME_HARI = 14;

    public const DEFAULT_SAFETY_STOCK = 2;

    private ?array $lightgbmStatus = null;

    public function lightgbmStatus(): ?array
    {
        return $this->lightgbmStatus;
    }

    public function analyze(?int $branchId, int $periodeHari, ?CarbonInterface $tanggalAkhir = null, string $method = 'SMA'): Collection
    {
        $this->lightgbmStatus = null;
        $end = CarbonImmutable::instance($tanggalAkhir ?? now('Asia/Jakarta')->subDay())->startOfDay();
        $groups = app(DailySalesSeries::class)->groups($branchId, $end, config('forecast.history_days'));
        $configs = KonfigurasiPersediaan::where('method', $method)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get()->keyBy(fn ($r) => $r->branch_id.'|'.$r->kategori.'|'.DailySalesSeries::karat($r->kadar));
        $cached = DB::table('inventory_forecasts')->where('cutoff', $end->toDateString())->where('version', config('forecast.version'))->where('method', 'ARIMA')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get()->keyBy(fn ($r) => DailySalesSeries::key($r->branch_id, $r->category_id, $r->kadar).'|'.$r->horizon);
        $lightgbm = [];
        if ($method === 'LightGBM') {
            $service = app(LightgbmForecastService::class);
            $this->lightgbmStatus = $service->status();
            $jobs = [];
            foreach ($groups as $g) {
                $lead = $configs->get($g['branch_id'].'|'.$g['kategori'].'|'.$g['kadar'])?->lead_time_hari ?? self::DEFAULT_LEAD_TIME_HARI;
                $jobs[] = ['group' => $g, 'horizon' => $lead, 'use' => 'lead_time'];
                $jobs[] = ['group' => $g, 'horizon' => $periodeHari, 'use' => 'projection'];
            }
            if ($this->lightgbmStatus['state'] === 'ready') {
                $lightgbm = $service->predict($jobs, $end);
            } else {
                foreach ($jobs as $job) {
                    $lightgbm[LightgbmForecastService::key($job['group'], $job['horizon'], $job['use'])] = [
                        'units' => null, 'reason' => $this->lightgbmStatus['message'], 'version' => null,
                    ];
                }
            }
        }
        $rows = collect($groups)->map(function ($g) use ($configs, $cached, $lightgbm, $periodeHari, $method, $end) {
            $cfg = $configs->get($g['branch_id'].'|'.$g['kategori'].'|'.$g['kadar']);
            $lead = $cfg?->lead_time_hari ?? self::DEFAULT_LEAD_TIME_HARI;
            $ss = $cfg?->safety_stock ?? self::DEFAULT_SAFETY_STOCK;
            $current = array_slice($g['values'], -$periodeHari);
            $previous = array_slice($g['values'], -2 * $periodeHari, $periodeHari);
            $sales = array_sum($current);
            $prior = array_sum($previous);
            $leadForecast = $this->forecast($current, $cached->get($g['key'].'|'.$lead), $g['input_hash'], $lead, $method, $end, $lightgbm[LightgbmForecastService::key($g, $lead, 'lead_time')] ?? null);
            $periodForecast = $this->forecast($current, $cached->get($g['key'].'|'.$periodeHari), $g['input_hash'], $periodeHari, $method, $end, $lightgbm[LightgbmForecastService::key($g, $periodeHari, 'projection')] ?? null);
            $forecast = $leadForecast['units'];
            $rop = $forecast === null ? null : (int) ceil($forecast + $ss);
            $q = $rop === null ? null : max(0, $rop - $g['stok_tersedia']);
            $change = $prior === 0 ? null : 100 * ($sales - $prior) / $prior;

            return ['branch_id' => $g['branch_id'], 'category_id' => $g['category_id'], 'cabang' => $g['cabang'], 'kategori' => $g['kategori'], 'kadar' => $g['kadar'], 'stok_tersedia' => $g['stok_tersedia'],
                'penjualan_periode_berjalan' => $sales, 'penjualan_periode_sebelumnya' => $prior, 'rata_rata_penjualan_harian' => ForecastMath::daily($current, 'SMA'),
                'estimasi_harian_metode' => in_array($method, ['SMA', 'WMA'], true) ? ForecastMath::daily($current, $method) : null,
                'persentase_perubahan' => $change, 'status_tren' => in_array(null, array_merge($current, $previous), true) ? 'data_belum_lengkap' : ($prior === 0 && $sales > 0 ? 'baru' : ($change >= 10 ? 'meningkat' : ($change !== null && $change <= -10 ? 'menurun' : 'stabil'))),
                'lead_time_hari' => $lead, 'safety_stock' => $ss, 'rop' => $rop, 'jumlah_perlu_disiapkan' => $q, 'status_persediaan' => $rop === null ? (in_array($method, ['ARIMA', 'LightGBM'], true) ? 'prediksi_belum_tersedia' : 'data_belum_lengkap') : ForecastMath::status($g['stok_tersedia'], $rop, $q),
                'menggunakan_konfigurasi_default' => $cfg === null, 'metode_diminta' => $method, 'metode_efektif' => $leadForecast['method'], 'alasan_fallback' => $leadForecast['reason'], 'prediksi_unit' => $forecast, 'tanggal_potong' => $end->toDateString(),
                'prediksi_unit_periode' => $periodForecast['units'], 'metode_efektif_periode' => $periodForecast['method'], 'alasan_fallback_periode' => $periodForecast['reason'],
                'versi_model' => $leadForecast['version'] ?? null, 'versi_model_periode' => $periodForecast['version'] ?? null,
                'riwayat_mulai' => $leadForecast['history_start'], 'riwayat_mulai_periode' => $periodForecast['history_start'], 'riwayat_akhir' => $end->toDateString()];
        });
        $priority = ['kritis' => 0, 'perlu_perhatian' => 1, 'aman' => 2, 'prediksi_belum_tersedia' => 3, 'data_belum_lengkap' => 4];

        return $rows->sortBy(fn ($r) => [$priority[$r['status_persediaan']], -($r['jumlah_perlu_disiapkan'] ?? 0), $r['cabang'], $r['kategori'], $r['kadar']])->values();
    }

    private function forecast(array $current, ?object $cache, string $inputHash, int $horizon, string $method, CarbonImmutable $end, ?array $prediction = null): array
    {
        $effective = $method;
        $reason = null;
        $historyStart = $end->subDays(count($current) - 1)->toDateString();
        if ($method === 'LightGBM') {
            if (($prediction['units'] ?? null) !== null) {
                return ['units' => $prediction['units'], 'method' => 'LightGBM', 'reason' => null, 'version' => $prediction['version'], 'history_start' => $end->subDays(89)->toDateString()];
            }

            return ['units' => null, 'method' => null, 'reason' => $prediction['reason'] ?? 'Prediksi LightGBM belum tersedia.', 'history_start' => $end->subDays(89)->toDateString()];
        }
        if ($method === 'ARIMA') {
            if ($cache && $cache->status === 'ok' && $cache->input_hash === $inputHash && is_numeric($cache->total) && is_finite((float) $cache->total) && $cache->total >= 0) {
                return [
                    'units' => (float) $cache->total,
                    'method' => 'ARIMA',
                    'reason' => null,
                    'history_start' => $end->subDays((json_decode($cache->details, true)['history_days'] ?? config('forecast.history_days')) - 1)->toDateString(),
                ];
            }
            $reason = $cache && $cache->input_hash !== $inputHash ? 'Riwayat berubah; batch perlu diperbarui.' : ($cache ? (json_decode($cache->details, true)['reason'] ?? 'Model tidak tersedia.') : 'Batch sesuai tanggal potong dan horizon belum tersedia.');
            $reason = str_replace('; gunakan SMA.', '; model ARIMA tidak diestimasi.', $reason);

            return ['units' => null, 'method' => null, 'reason' => $reason, 'history_start' => $end->subDays(config('forecast.history_days') - 1)->toDateString()];
        }
        $daily = ForecastMath::daily($current, $effective);

        return [
            'units' => $daily === null ? null : $daily * $horizon,
            'method' => $daily === null ? null : $effective,
            'reason' => $daily === null ? 'Pencatatan belum lengkap pada jendela riwayat.' : $reason,
            'history_start' => $historyStart,
        ];
    }
}
