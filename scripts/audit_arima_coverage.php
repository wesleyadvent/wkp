<?php

use App\Services\DailySalesSeries;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Read-only database audit. No sales, calendars, or model settings are changed.
$calendar = DB::table('kalender_cabang')->selectRaw('branch_id, MIN(tanggal) AS first_date, MAX(CASE WHEN pencatatan_lengkap = 1 THEN tanggal END) AS last_complete')->groupBy('branch_id')->get();
if ($calendar->isEmpty() || $calendar->contains(fn ($row) => ! $row->last_complete)) {
    throw new RuntimeException('Riwayat kalender lengkap belum tersedia.');
}
$start = CarbonImmutable::parse($calendar->max('first_date'), 'Asia/Jakarta');
$end = CarbonImmutable::parse($calendar->min('last_complete'), 'Asia/Jakarta');
$days = (int) $start->diffInDays($end) + 1;
$groups = app(DailySalesSeries::class)->groups(null, $end, $days);
$report = [
    'database' => DB::connection()->getDatabaseName(),
    'checked_at' => now('Asia/Jakarta')->toIso8601String(),
    'history_from' => $start->toDateString(),
    'cutoff_from' => $start->addDays(89)->toDateString(),
    'cutoff_through' => $end->toDateString(),
    'cutoff_days' => max(0, $days - 89),
    'groups' => count($groups),
    'group_date_windows' => 0,
    'minimum_positive_days' => PHP_INT_MAX,
    'ineligible_windows' => 0,
    'examples' => [],
    'available_stock_by_branch' => DB::table('barang')->where('status', 'tersedia')->whereNull('deleted_at')->groupBy('branch_id')->selectRaw('branch_id, COUNT(*) AS n')->pluck('n', 'branch_id')->all(),
];
foreach ($groups as $group) {
    for ($cutoff = 89; $cutoff < $days; $cutoff++) {
        $left = max(0, $cutoff - 179);
        for ($i = $cutoff; $i >= $left; $i--) {
            if ($group['values'][$i] === null) {
                $left = $i + 1;
                break;
            }
        }
        $window = array_slice($group['values'], $left, $cutoff - $left + 1);
        $positive = count(array_filter($window, fn ($value) => $value > 0));
        $report['minimum_positive_days'] = min($report['minimum_positive_days'], $positive);
        $report['group_date_windows']++;
        if (count($window) < 90 || $positive < 14 || count(array_unique($window)) <= 1) {
            $report['ineligible_windows']++;
            if (count($report['examples']) < 20) {
                $report['examples'][] = ['group' => $group['key'], 'cutoff' => $start->addDays($cutoff)->toDateString(), 'history_days' => count($window), 'positive_days' => $positive];
            }
        }
    }
}
$output = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents(storage_path('app/private/arima-coverage-audit.json'), $output);
echo $output;
exit($report['ineligible_windows'] === 0 && $report['group_date_windows'] > 0 ? 0 : 1);
