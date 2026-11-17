<?php

namespace App\Console\Commands;

use App\Services\ArimaBatch;
use App\Services\DailySalesSeries;
use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BuildArimaForecasts extends Command
{
    protected $signature = 'forecast:arima {--cutoff=} {--branch=}';

    protected $description = 'Bangun batch ARIMA berdasarkan data lengkap sampai tanggal potong';

    public function handle(): int
    {
        $args = Validator::make(['cutoff' => $this->option('cutoff') ?: now('Asia/Jakarta')->subDay()->toDateString(), 'branch' => $this->option('branch')], ['cutoff' => 'required|date_format:Y-m-d|before:today', 'branch' => 'nullable|integer|exists:cabang,id'])->validate();
        $groups = app(DailySalesSeries::class)->groups($args['branch'] ? (int) $args['branch'] : null, CarbonImmutable::parse($args['cutoff'], 'Asia/Jakarta'), config('forecast.history_days'));
        $configs = DB::table('konfigurasi_persediaan')->where('method', 'ARIMA')->get()->keyBy(fn ($c) => $c->branch_id.'|'.$c->kategori.'|'.DailySalesSeries::karat($c->kadar));
        foreach ($groups as &$g) {
            $lead = $configs->get($g['branch_id'].'|'.$g['kategori'].'|'.$g['kadar'])?->lead_time_hari ?? InventoryAnalysisService::DEFAULT_LEAD_TIME_HARI;
            $g['horizons'] = array_values(array_unique([$lead, ...InventoryAnalysisService::PERIODS]));
            $g['horizon'] = max($g['horizons']);
        }
        unset($g);
        try {
            $results = app(ArimaBatch::class)->run($groups);
            $forecasts = [];
            foreach ($groups as $g) {
                $result = $results[$g['key'].'|'.$g['horizon']];
                if ($result['status'] === 'ok') {
                    $daily = $result['daily'] ?? [];
                    if (count($daily) !== $g['horizon'] || collect($daily)->contains(fn ($value) => ! is_numeric($value) || ! is_finite((float) $value) || $value < 0)) {
                        throw new \RuntimeException('Ramalan harian ARIMA tidak lengkap atau tidak valid.');
                    }
                }
                foreach ($g['horizons'] as $horizon) {
                    $r = $result;
                    $r['horizon'] = $horizon;
                    if ($r['status'] === 'ok') {
                        $r['daily'] = array_slice($daily, 0, $horizon);
                        $r['total'] = array_sum($r['daily']);
                    }
                    $forecasts[] = ['group' => $g, 'result' => $r];
                }
            }
        } catch (\Throwable $e) {
            $this->error('Batch gagal; hasil lama tidak ditimpa: '.$e->getMessage());

            return self::FAILURE;
        }
        DB::transaction(function () use ($forecasts) {
            foreach ($forecasts as $forecast) {
                $g = $forecast['group'];
                $r = $forecast['result'];
                DB::table('inventory_forecasts')->updateOrInsert(['branch_id' => $g['branch_id'], 'category_id' => $g['category_id'], 'kadar' => $g['kadar'], 'cutoff' => $g['cutoff'], 'horizon' => $r['horizon'], 'method' => 'ARIMA', 'version' => config('forecast.version')], ['input_hash' => $g['input_hash'], 'status' => $r['status'], 'total' => $r['total'], 'details' => json_encode($r, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->info('Batch tersimpan: '.count($groups).' kelompok, '.count($forecasts).' ramalan; berhasil '.collect($forecasts)->where('result.status', 'ok')->count().'.');

        return self::SUCCESS;
    }
}
