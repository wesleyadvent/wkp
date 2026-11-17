<?php

namespace App\Console\Commands;

use App\Services\ArimaBatch;
use App\Services\DailySalesSeries;
use App\Services\ForecastMath;
use App\Support\BranchCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluateForecasts extends Command
{
    protected $signature = 'forecast:evaluate {--branch=} {--stride=28 : Jarak tanggal potong dalam hari} {--group-limit=0 : 0 berarti seluruh kelompok} {--output=}';

    protected $description = 'Komparasi rolling 14 hari; pilih jendela SMA/WMA pada validasi lalu bekukan untuk uji';

    public function handle(): int
    {
        $args = Validator::make(['branch' => $this->option('branch'), 'stride' => $this->option('stride'), 'limit' => $this->option('group-limit')], ['branch' => 'nullable|integer|exists:cabang,id', 'stride' => 'required|integer|min:1|max:365', 'limit' => 'required|integer|min:0'])->validate();
        $end = CarbonImmutable::parse('2026-07-31', 'Asia/Jakarta');
        $count = 0;
        $records = [];
        $selection = [];
        $branches = DB::table('cabang')->when($args['branch'], fn ($q) => $q->where('id', $args['branch']))->pluck('id');
        foreach ($branches as $branch) {
            $groups = app(DailySalesSeries::class)->groups($branch, $end, 1308);
            ksort($groups);
            foreach ($groups as $g) {
                if ($args['limit'] && $count >= (int) $args['limit']) {
                    break 2;
                }
                $count++;
                $samples = [];
                $requests = [];
                $seriesStart = CarbonImmutable::parse($g['start'], 'Asia/Jakarta');
                foreach (['validasi' => ['2025-01-01', '2025-09-30'], 'uji' => ['2025-10-01', '2026-07-31']] as $split => $bounds) {
                    for ($cut = CarbonImmutable::parse($bounds[0], 'Asia/Jakarta'); $cut->addDays(14)->toDateString() <= $bounds[1]; $cut = $cut->addDays((int) $args['stride'])) {
                        $offset = (int) $seriesStart->diffInDays($cut);
                        $history = array_slice($g['values'], max(0, $offset - 179), 180);
                        $actualValues = array_slice($g['values'], $offset + 1, 14);
                        if (count($actualValues) !== 14 || in_array(null, $actualValues, true)) {
                            continue;
                        }
                        $pred = [];
                        foreach ([30, 60, 90] as $window) {
                            foreach (['SMA', 'WMA'] as $method) {
                                $daily = ForecastMath::daily(array_slice($history, -$window), $method);
                                $pred[$method.$window] = $daily === null ? null : $daily * 14;
                            }
                        }
                        if ($pred['SMA30'] === null) {
                            continue;
                        }
                        $key = $g['key'].'@'.$cut->toDateString();
                        $requests[] = ['key' => $key, 'cutoff' => $cut->toDateString(), 'horizon' => 14, 'values' => $history, 'input_hash' => hash('sha256', json_encode($history))];
                        $eid = false;
                        foreach (BranchCalendar::EID_DATES as $day) {
                            if (abs($cut->diffInDays(CarbonImmutable::parse($day, 'Asia/Jakarta'), false)) <= 21) {
                                $eid = true;
                            }
                        }
                        $samples[$key] = ['group' => $g['key'], 'cutoff' => $cut->toDateString(), 'target_end' => $cut->addDays(14)->toDateString(), 'split' => $split, 'lebaran' => $eid, 'actual' => array_sum($actualValues), 'predictions' => $pred];
                    }
                }
                if (! $requests) {
                    continue;
                }
                try {
                    $results = app(ArimaBatch::class)->run($requests);
                } catch (\Throwable $e) {
                    $this->error($e->getMessage());

                    return self::FAILURE;
                }
                $windows = [];
                foreach (['SMA', 'WMA'] as $method) {
                    $best = null;
                    foreach ([30, 60, 90] as $window) {
                        $errors = [];
                        foreach ($samples as $s) {
                            if ($s['split'] === 'validasi' && $s['predictions'][$method.$window] !== null) {
                                $errors[] = abs($s['predictions'][$method.$window] - $s['actual']);
                            }
                        }
                        if ($errors && ($best === null || array_sum($errors) / count($errors) < $best)) {
                            $best = array_sum($errors) / count($errors);
                            $windows[$method] = $window;
                        }
                    }
                }
                if (count($windows) !== 2) {
                    continue;
                }
                $selection[$g['key']] = $windows;
                foreach ($samples as $key => $s) {
                    $arima = $results[$key.'|14'];
                    $s['arima_ok'] = $arima['status'] === 'ok';
                    $s['fallback_reason'] = $arima['reason'] ?? null;
                    $s['predictions'] = ['SMA30' => $s['predictions']['SMA30'], 'SMA' => $s['predictions']['SMA'.$windows['SMA']], 'WMA' => $s['predictions']['WMA'.$windows['WMA']], 'ARIMA' => $s['arima_ok'] ? $arima['total'] : $s['predictions']['SMA'.$windows['SMA']]];
                    $records[] = $s;
                }
                $this->line('Kelompok '.$g['key'].' selesai: '.count($samples).' tanggal potong.');
            }
        }
        $metrics = [];
        foreach (['validasi', 'uji'] as $split) {
            foreach (['semua', 'lebaran'] as $scope) {
                foreach (['irisan_berhasil', 'operasional'] as $mode) {
                    $rows = array_filter($records, fn ($r) => $r['split'] === $split && ($scope === 'semua' || $r['lebaran']) && ($mode === 'operasional' || $r['arima_ok']));
                    foreach (['SMA30', 'SMA', 'WMA', 'ARIMA'] as $method) {
                        $errors = [];
                        foreach ($rows as $r) {
                            if ($r['predictions'][$method] !== null) {
                                $errors[] = $r['predictions'][$method] - $r['actual'];
                            }
                        }
                        $n = count($errors);
                        $metrics[] = ['split' => $split, 'cakupan' => $scope, 'mode' => $mode, 'method' => $method, 'n' => $n, 'mae' => $n ? array_sum(array_map('abs', $errors)) / $n : null, 'rmse' => $n ? sqrt(array_sum(array_map(fn ($x) => $x * $x, $errors)) / $n) : null, 'bias' => $n ? array_sum($errors) / $n : null];
                    }
                }
            }
        }
        $coverage = [];
        foreach (['validasi', 'uji'] as $split) {
            foreach (['semua', 'lebaran'] as $scope) {
                $rows = array_filter($records, fn ($r) => $r['split'] === $split && ($scope === 'semua' || $r['lebaran']));
                $success = count(array_filter($rows, fn ($r) => $r['arima_ok']));
                $coverage[] = ['split' => $split, 'scope' => $scope, 'total' => count($rows), 'success' => $success, 'fallback' => count($rows) - $success];
            }
        }
        $path = $this->option('output') ?: storage_path('app/private/forecast-evaluation.json');
        file_put_contents($path, json_encode(['version' => config('forecast.version'), 'horizon' => 14, 'stride' => (int) $args['stride'], 'group_limit' => (int) $args['limit'], 'selection_on_validation' => $selection, 'samples' => count($records), 'arima_success' => count(array_filter($records, fn ($r) => $r['arima_ok'])), 'fallbacks' => count(array_filter($records, fn ($r) => ! $r['arima_ok'])), 'coverage' => $coverage, 'metrics' => $metrics, 'rows' => $records, 'note' => 'Data sintetis. Pemilihan jendela menggunakan validasi; metrik validasi merupakan metrik seleksi. Laporkan uji akhir pada tanggal/kelompok sama. Ini sampling jika stride > 1 atau group_limit > 0.'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info('Evaluasi tersimpan: '.$path);

        return self::SUCCESS;
    }
}
