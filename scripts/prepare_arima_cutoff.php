<?php

use App\Services\ArimaForecastBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$args = Validator::make(['cutoff' => $argv[1] ?? now('Asia/Jakarta')->subDay()->toDateString()], ['cutoff' => 'required|date_format:Y-m-d|before:today'])->validate();
$end = CarbonImmutable::parse($args['cutoff'], 'Asia/Jakarta');
$builder = app(ArimaForecastBuilder::class);
$pending = $builder->pending(null, $end);
if (in_array('--retry-failed', $argv, true)) {
    $failed = DB::table('inventory_forecasts')->where('method', 'ARIMA')->where('version', config('forecast.version'))
        ->where('cutoff', $args['cutoff'])->whereIn('status', ['timeout', 'error'])->get()
        ->mapWithKeys(fn ($row) => [$row->branch_id.'|'.$row->category_id.'|'.$row->kadar => true]);
    $pending = collect($pending)->keyBy('key');
    foreach ($builder->groups(null, $end) as $group) {
        if ($failed->has($group['key'])) {
            $pending->put($group['key'], $group);
        }
    }
    $pending = $pending->values()->all();
}
echo 'Tanggal potong '.$args['cutoff'].': '.count($pending).' kelompok perlu dihitung.'.PHP_EOL;
$done = 0;
foreach (array_chunk($pending, 10) as $chunk) {
    $started = microtime(true);
    $rows = $builder->build($chunk);
    $done += count($chunk);
    echo json_encode([
        'prepared_groups' => $done, 'total_groups' => count($pending),
        'seconds' => round(microtime(true) - $started, 1),
        'statuses' => array_count_values(array_column(array_column($rows, 'result'), 'status')),
    ], JSON_THROW_ON_ERROR).PHP_EOL;
    flush();
}
echo 'Kelompok tersisa: '.count($builder->pending(null, $end)).PHP_EOL;
