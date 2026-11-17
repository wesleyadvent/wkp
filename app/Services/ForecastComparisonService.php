<?php

namespace App\Services;

use RuntimeException;

class ForecastComparisonService
{
    public const METHODS = ['SMA', 'WMA', 'ARIMA', 'LightGBM'];

    public const LEAD_TIME_HORIZONS = [3, 7, 14, 21];

    public const HORIZONS = [...self::LEAD_TIME_HORIZONS, 30, 60, 90];

    public const FIELDS = ['horizon', 'branch_code', 'category', 'karat', 'cutoff', 'target_start', 'target_end', 'actual', ...self::METHODS];

    public function metadata(): array
    {
        $path = config('forecast.comparison.metadata');
        if (! is_file($path)) {
            throw new RuntimeException('Hasil evaluasi belum tersedia. Admin perlu membangun hasil perbandingan terlebih dahulu.');
        }
        $meta = json_decode(file_get_contents($path), true);
        if (($meta['schema_version'] ?? null) !== 'forecast-comparison-v1' || ! is_array($meta['horizons'] ?? null)) {
            throw new RuntimeException('Metadata evaluasi tidak valid. Bangun ulang hasil perbandingan.');
        }

        return $meta;
    }

    public function analyze(array $filters, array $branchNames, int $page, int $perPage): array
    {
        $meta = $this->metadata();
        $path = config('forecast.comparison.file');
        if (! is_file($path) || ! hash_equals($meta['csv_sha256'] ?? '', hash_file('sha256', $path))) {
            throw new RuntimeException('Berkas evaluasi belum lengkap atau berubah. Bangun ulang hasil perbandingan.');
        }
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('Berkas evaluasi tidak dapat dibaca.');
        }
        $accumulators = [];
        foreach (self::METHODS as $method) {
            $accumulators[$method] = new ForecastErrorMetrics;
        }
        $count = $excluded = 0;
        $details = $groups = $dates = [];
        $first = $last = $targetEnd = null;
        try {
            if (fgetcsv($handle, escape: '') !== self::FIELDS) {
                throw new RuntimeException('Kolom evaluasi tidak sesuai kontrak. Bangun ulang hasil perbandingan.');
            }
            while (($values = fgetcsv($handle, escape: '')) !== false) {
                if (count($values) !== count(self::FIELDS)) {
                    throw new RuntimeException('Baris evaluasi tidak lengkap. Bangun ulang hasil perbandingan.');
                }
                $row = array_combine(self::FIELDS, $values);
                if ((int) $row['horizon'] !== $filters['horizon'] || ($filters['branch_code'] !== null && $row['branch_code'] !== $filters['branch_code'])
                    || ($filters['category'] !== '' && $row['category'] !== $filters['category']) || ($filters['karat'] !== null && (int) $row['karat'] !== $filters['karat'])
                    || $row['cutoff'] < $filters['from'] || $row['cutoff'] > $filters['to']) {
                    continue;
                }
                $branchName = $branchNames[$row['branch_code']] ?? $row['branch_code'];
                if ($filters['q'] !== '' && ! str_contains(mb_strtolower($branchName.' '.$row['category'].' '.$row['karat'].'K'), mb_strtolower($filters['q']))) {
                    continue;
                }
                $valid = true;
                foreach (['actual', ...self::METHODS] as $field) {
                    if (! is_numeric($row[$field]) || ! is_finite((float) $row[$field]) || (float) $row[$field] < 0) {
                        $valid = false;
                    }
                }
                if (! $valid) {
                    $excluded++;

                    continue;
                }
                $actual = (float) $row['actual'];
                $predictions = [];
                foreach (self::METHODS as $method) {
                    $predictions[$method] = (float) $row[$method];
                    $accumulators[$method]->add($actual, $predictions[$method]);
                }
                if ($count >= ($page - 1) * $perPage && count($details) < $perPage) {
                    $details[] = ['branch' => $branchName, 'category' => $row['category'], 'karat' => (int) $row['karat'], 'cutoff' => $row['cutoff'],
                        'target_start' => $row['target_start'], 'target_end' => $row['target_end'], 'actual' => $actual, 'predictions' => $predictions];
                }
                $count++;
                $groups[$row['branch_code'].'|'.$row['category'].'|'.$row['karat']] = true;
                $dates[$row['cutoff']] = true;
                $first = $first === null ? $row['cutoff'] : min($first, $row['cutoff']);
                $last = $last === null ? $row['cutoff'] : max($last, $row['cutoff']);
                $targetEnd = $targetEnd === null ? $row['target_end'] : max($targetEnd, $row['target_end']);
            }
        } finally {
            fclose($handle);
        }
        $rankings = [];
        foreach ($accumulators as $method => $accumulator) {
            $rankings[] = ['method' => $method, 'variant' => $meta['horizons'][(string) $filters['horizon']]['selected'][$method] ?? $method, ...$accumulator->result()];
        }
        $metric = $filters['metric'];
        usort($rankings, fn ($a, $b) => (($a[$metric] ?? INF) <=> ($b[$metric] ?? INF)) ?: strcmp($a['method'], $b['method']));
        $previous = null;
        $rank = null;
        foreach ($rankings as $index => &$row) {
            if ($row[$metric] !== null && ($previous === null || abs($row[$metric] - $previous) > 1e-9)) {
                $rank = $index + 1;
            }
            $row['rank'] = $row[$metric] === null ? null : $rank;
            $previous = $row[$metric];
        }
        unset($row);
        $best = $metricWinners = [];
        foreach (['mae', 'rmse', 'mape'] as $key) {
            $scores = array_values(array_filter(array_column($rankings, $key), fn ($v) => $v !== null));
            $best[$key] = $scores ? min($scores) : null;
            $metricWinners[$key] = array_values(array_column(array_filter($rankings, fn ($r) => $r[$key] !== null && abs($r[$key] - $best[$key]) <= 1e-9), 'method'));
        }

        return ['metadata' => $meta, 'rankings' => $rankings, 'best' => $best, 'metric_winners' => $metricWinners,
            'winners' => array_values(array_column(array_filter($rankings, fn ($r) => $r['rank'] === 1), 'method')),
            'count' => $count, 'excluded' => $excluded, 'group_count' => count($groups), 'date_count' => count($dates), 'first_cutoff' => $first, 'last_cutoff' => $last, 'target_end' => $targetEnd,
            'zero_actual' => $rankings[0]['zero_actual'], 'mape_n' => $rankings[0]['mape_n'], 'details' => $details];
    }
}
