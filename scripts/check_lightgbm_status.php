<?php

use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$health = Http::timeout(10)->get(rtrim(config('forecast.lightgbm.url'), '/').'/health')->throw()->json();
$cutoffs = array_slice($argv, 1) ?: [now('Asia/Jakarta')->subDay()->toDateString()];
$report = ['database' => DB::connection()->getDatabaseName(), 'model_directory' => config('forecast.lightgbm.model_dir'), 'health' => $health, 'cutoffs' => []];
$passed = ($health['ready'] ?? false) === true && array_column($health['models'] ?? [], 'horizon_days') === [3, 7, 14, 21, 30, 60, 90];
foreach ($cutoffs as $cutoff) {
    $end = CarbonImmutable::createFromFormat('!Y-m-d', $cutoff, 'Asia/Jakarta');
    if (! $end || $end->toDateString() !== $cutoff) {
        throw new InvalidArgumentException('Tanggal potong harus YYYY-MM-DD.');
    }
    fwrite(STDERR, 'Checking LightGBM '.$cutoff.'...'.PHP_EOL);
    $entry = ['cutoff' => $cutoff];
    foreach ([30, 60, 90] as $period) {
        $rows = app(InventoryAnalysisService::class)->analyze(null, $period, $end, 'LightGBM');
        $missing = $rows->filter(fn ($row) => $row['prediksi_unit'] === null || $row['prediksi_unit_periode'] === null || $row['rop'] === null);
        $entry['windows'][$period] = [
            'groups' => $rows->count(),
            'lead_time_available' => $rows->filter(fn ($row) => $row['metode_efektif'] === 'LightGBM' && $row['prediksi_unit'] !== null)->count(),
            'projection_available' => $rows->filter(fn ($row) => $row['metode_efektif_periode'] === 'LightGBM' && $row['prediksi_unit_periode'] !== null)->count(),
            'lead_time_groups' => $rows->countBy('lead_time_hari')->all(),
            'unavailable' => $missing->map(fn ($row) => array_intersect_key($row, array_flip(['cabang', 'kategori', 'kadar', 'alasan_fallback', 'alasan_fallback_periode'])))->values()->all(),
        ];
        $passed = $passed && $rows->count() === 340 && $missing->isEmpty();
    }
    $report['cutoffs'][] = $entry;
}
$report['passed'] = $passed;
$output = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents(storage_path('app/private/lightgbm-status.json'), $output);
echo $output;
exit($passed ? 0 : 1);
