<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ArimaBatch
{
    public function run(array $groups): array
    {
        $process = new Process([config('forecast.python'), base_path('scripts/forecast/arima_batch.py')]);
        $process->setTimeout(config('forecast.batch_timeout'));
        $process->setInput(json_encode(['version' => config('forecast.version'), 'group_timeout' 
        => config('forecast.group_timeout'), 'groups' => array_values($groups)], JSON_THROW_ON_ERROR));
        $process->mustRun();
        $output = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        
        if (($output['version'] ?? null) !== config('forecast.version') || count($output['results'] ?? []) !== count($groups)) {
            throw new \RuntimeException('Batch ARIMA tidak lengkap atau versi salah.');
        }
        $expected = [];
        foreach ($groups as $g) {
            $expected[$g['key'].'|'.$g['horizon']] = $g;
        }
        $results = [];
        foreach ($output['results'] as $r) {
            $id = ($r['key'] ?? '').'|'.($r['horizon'] ?? '');
            $g = $expected[$id] ?? null;
            if (! $g || isset($results[$id]) || ($r['input_hash'] ?? null) !== $g['input_hash'] || ($r['cutoff'] ?? null) !== $g['cutoff']) {
                throw new \RuntimeException('Identitas hasil ARIMA tidak sesuai.');
            }
            if (($r['status'] ?? null) === 'ok' && (! is_numeric($r['total'] ?? null) || ! is_finite((float) $r['total']) || $r['total'] < 0)) {
                throw new \RuntimeException('Prediksi ARIMA tidak valid.');
            }
            $results[$id] = $r;
        }

        return $results;
    }
}
