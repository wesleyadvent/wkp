<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ArimaForecastBuilder
{
    public function groups(?int $branch, CarbonImmutable $end): array
    {
        $groups = app(DailySalesSeries::class)->groups($branch, $end, config('forecast.history_days'));
        $configs = DB::table('konfigurasi_persediaan')->where('method', 'ARIMA')->get()
            ->keyBy(fn ($c) => $c->branch_id.'|'.$c->kategori.'|'.DailySalesSeries::karat($c->kadar));
        foreach ($groups as &$group) {
            $lead = $configs->get($group['branch_id'].'|'.$group['kategori'].'|'.$group['kadar'])?->lead_time_hari ?? InventoryAnalysisService::DEFAULT_LEAD_TIME_HARI;
            $group['horizons'] = array_values(array_unique([$lead, ...InventoryAnalysisService::PERIODS]));
            $group['horizon'] = max($group['horizons']);
        }

        return $groups;
    }

    public function pending(?int $branch, CarbonImmutable $end): array
    {
        $cached = DB::table('inventory_forecasts')->where('method', 'ARIMA')->where('version', config('forecast.version'))
            ->where('cutoff', $end->toDateString())->when($branch, fn ($q) => $q->where('branch_id', $branch))->get()
            ->keyBy(fn ($r) => DailySalesSeries::key($r->branch_id, $r->category_id, $r->kadar).'|'.$r->horizon);

        return array_values(array_filter($this->groups($branch, $end), 
        function ($group) use ($cached) {
            foreach ($group['horizons'] as $horizon) {
                $row = $cached->get($group['key'].'|'.$horizon);
                if (! $row || $row->input_hash !== $group['input_hash']) {
                    return true;
                }
            }

            return false;
        }));
    }

    public function prepare(?int $branch, CarbonImmutable $end): array
    {
        $lock = Cache::lock('arima-prepare:'.$end->toDateString(), 
        config('forecast.group_timeout') + 60);
        if (! $lock->get()) {
            return ['complete' => false, 'busy' => true, 'remaining' => null];
        }

        try {
            $pending = $this->pending($branch, $end);
            if ($pending !== []) {
                $this->build([$pending[0]]);
            }
            $remaining = max(0, count($pending) - 1);

            return ['complete' => $remaining === 0, 'busy' => false, 'remaining' => $remaining];
        } finally {
            $lock->release();
        }
    }

    public function build(array $groups): array
    {
        $results = app(ArimaBatch::class)->run($groups);
        $forecasts = [];
        foreach ($groups as $group) {
            $result = $results[$group['key'].'|'.$group['horizon']];
            if ($result['status'] === 'ok') {
                $daily = $result['daily'] ?? [];
                if (count($daily) !== $group['horizon'] || collect($daily)->contains(fn ($value) 
                    => ! is_numeric($value) || ! is_finite((float) $value) || $value < 0)) {
                    throw new \RuntimeException('Prediksi harian ARIMA tidak lengkap atau tidak valid.');
                }
            }
            foreach ($group['horizons'] as $horizon) {
                $row = $result;
                $row['horizon'] = $horizon;
                if ($row['status'] === 'ok') {
                    $row['daily'] = array_slice($daily, 0, $horizon);
                    $row['total'] = array_sum($row['daily']);
                }
                $forecasts[] = ['group' => $group, 'result' => $row];
            }
        }

        DB::transaction(function () use ($forecasts) {
            foreach ($forecasts as $forecast) {
                $group = $forecast['group'];
                $row = $forecast['result'];
                DB::table('inventory_forecasts')->updateOrInsert([
                    'branch_id' => $group['branch_id'], 'category_id' 
                    => $group['category_id'], 'kadar' => $group['kadar'],'cutoff' 
                    => $group['cutoff'], 'horizon' => $row['horizon'], 'method' 
                    => 'ARIMA', 'version' => config('forecast.version'),
                ], [
                    'input_hash' => $group['input_hash'], 'status' 
                    => $row['status'], 'total' => $row['total'],'details' 
                    => json_encode($row, JSON_THROW_ON_ERROR), 
                    'created_at' => now(), 
                    'updated_at' => now(),
                ]);
            }
        });

        return $forecasts;
    }
}
