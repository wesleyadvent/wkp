<?php

use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$cutoffs = array_slice($argv, 1) ?: [now('Asia/Jakarta')->subDay()->toDateString()];
$report = ['database' => DB::connection()->getDatabaseName(), 'cutoffs' => []];
foreach ($cutoffs as $cutoff) {
    $end = CarbonImmutable::createFromFormat('!Y-m-d', $cutoff, 'Asia/Jakarta');
    if (! $end || $end->toDateString() !== $cutoff) {
        throw new InvalidArgumentException('Tanggal potong harus YYYY-MM-DD.');
    }
    $stored = DB::table('inventory_forecasts')->where('method', 'ARIMA')
        ->where('version', config('forecast.version'))->where('cutoff', $cutoff);
    $entry = ['cutoff' => $cutoff, 'stored_forecasts' => (clone $stored)->count(), 'stored_statuses' => (clone $stored)->groupBy('status')->selectRaw('status, COUNT(*) AS n')->pluck('n', 'status')->all()];
    foreach ([30, 60, 90] as $period) {
        $rows = app(InventoryAnalysisService::class)->analyze(null, $period, $end, 'ARIMA');
        $entry['windows'][$period] = [
            'groups' => $rows->count(),
            'lead_time_available' => $rows->filter(fn ($row) => $row['prediksi_unit'] !== null)->count(),
            'window_available' => $rows->filter(fn ($row) => $row['prediksi_unit_periode'] !== null)->count(),
            'missing_batch' => $rows->filter(fn ($row) => str_contains($row['alasan_fallback'] ?? '', 'Batch sesuai tanggal potong dan horizon belum tersedia'))->count(),
            'safety_stock_configured' => $rows->filter(fn ($row) => ! $row['menggunakan_konfigurasi_default'] && $row['safety_stock'] !== null)->count(),
            'unavailable_reasons' => $rows->filter(fn ($row) => $row['prediksi_unit'] === null)->countBy('alasan_fallback')->all(),
        ];
        if ($period === 30) {
            $entry['unavailable_groups'] = $rows->filter(fn ($row) => $row['prediksi_unit'] === null)->map(fn ($row) => array_intersect_key($row, array_flip(['cabang', 'kategori', 'kadar', 'alasan_fallback'])))->values()->all();
        }
    }
    $report['cutoffs'][] = $entry;
}
$output = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents(storage_path('app/private/arima-status.json'), $output);
echo $output;
