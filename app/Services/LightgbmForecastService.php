<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LightgbmForecastService
{
    public function __construct(private LightgbmFeatureBuilder $features) {}

    public function status(): array
    {
        try {
            $response = Http::acceptJson()->connectTimeout(config('forecast.lightgbm.connect_timeout'))
                ->timeout(config('forecast.lightgbm.timeout'))
                ->get(rtrim(config('forecast.lightgbm.url'), '/').'/health');

            if ($response->successful() && $response->json('ready') === true) {
                return ['state' => 'ready', 'message' => null];
            }

            return ['state' => 'not_ready', 'message' => 'Layanan Flask LightGBM merespons, tetapi model belum siap. Periksa log layanan dan kelengkapan model, lalu ulangi analisis.'];
        } catch (ConnectionException $e) {
            Log::warning('LightGBM health check failed', ['reason' => $e->getMessage()]);

            return ['state' => 'unreachable', 'message' => 'Layanan Flask LightGBM belum menyala atau tidak dapat dihubungi. Nyalakan layanan Flask, lalu klik Analisis kembali.'];
        }
    }

    public static function key(array $group, int $horizon, string $use): string
    {
        return $group['key'].'|'.$use.'|'.$horizon;
    }

    public function predict(array $jobs, CarbonImmutable $end): array
    {
        if (! $jobs) {
            return [];
        }
        $context = $this->features->context(array_column($jobs, 'group'), $end, max(array_column($jobs, 'horizon')));
        $results = $batches = [];
        foreach ($jobs as $job) {
            $g = $job['group'];
            $id = self::key($g, $job['horizon'], $job['use']);
            try {
                $metadata = $this->features->metadata($job['horizon'], $job['use']);
                $features = $this->features->build($g, $metadata, $end, $context);
                $requestGroup = ['group_key' => $g['key'], 'features' => $features];
                $cacheKey = 'lightgbm:'.hash('sha256', json_encode([$id, $end->toDateString(), $metadata, $features, $g['input_hash']], JSON_THROW_ON_ERROR));
                $cached = Cache::get($cacheKey);
                if ($this->validCached($cached, $g['key'], $end, $job, $metadata)) {
                    $results[$id] = ['units' => (float) $cached['predicted_units'], 'reason' => null, 'version' => $metadata['model_version']];

                    continue;
                }
                $batch = $job['use'].'|'.$job['horizon'];
                $batches[$batch]['metadata'] = $metadata;
                $batches[$batch]['job'] = $job;
                $batches[$batch]['groups'][$g['key']] = ['request' => $requestGroup, 'id' => $id, 'cache_key' => $cacheKey];
            } catch (\Throwable $e) {
                $results[$id] = ['units' => null, 'reason' => $e->getMessage(), 'version' => null];
            }
        }
        foreach ($batches as $batch) {
            foreach (array_chunk($batch['groups'], 500, true) as $groups) {
                $metadata = $batch['metadata'];
                $job = $batch['job'];
                try {
                    $response = Http::acceptJson()->connectTimeout(config('forecast.lightgbm.connect_timeout'))
                        ->timeout(config('forecast.lightgbm.timeout'))->post(rtrim(config('forecast.lightgbm.url'), '/').'/predict', [
                            'schema_version' => $metadata['schema_version'], 'model_version' => $metadata['model_version'],
                            'as_of_date' => $end->toDateString(), 'horizon_days' => $job['horizon'], 'forecast_use' => $job['use'],
                            'groups' => array_column($groups, 'request'),
                        ]);
                    if (! $response->successful()) {
                        throw new RuntimeException('Layanan LightGBM belum siap atau menolak fitur (HTTP '.$response->status().').');
                    }
                    $data = $response->json();
                    if (! is_array($data) || ($data['schema_version'] ?? null) !== $metadata['schema_version'] || ($data['model_version'] ?? null) !== $metadata['model_version'] || ($data['as_of_date'] ?? null) !== $end->toDateString() || ($data['horizon_days'] ?? null) !== $job['horizon'] || ($data['forecast_use'] ?? null) !== $job['use'] || ! is_array($data['predictions'] ?? null) || count($data['predictions']) !== count($groups)) {
                        throw new RuntimeException('Identitas atau cakupan respons LightGBM tidak sesuai.');
                    }
                    $predictions = [];
                    foreach ($data['predictions'] as $prediction) {
                        $key = $prediction['group_key'] ?? null;
                        $units = $prediction['predicted_units'] ?? null;
                        if (! is_string($key) || ! isset($groups[$key]) || isset($predictions[$key]) || ! $this->validUnits($units)) {
                            throw new RuntimeException('Kelompok atau nilai prediksi LightGBM tidak valid.');
                        }
                        $predictions[$key] = $units;
                    }
                    // Validate the entire response before caching any group from it.
                    foreach ($groups as $key => $group) {
                        $cached = ['group_key' => $key, 'as_of_date' => $end->toDateString(), 'horizon_days' => $job['horizon'], 'forecast_use' => $job['use'], 'model_version' => $metadata['model_version'], 'predicted_units' => $predictions[$key]];
                        Cache::put($group['cache_key'], $cached, now()->addSeconds(config('forecast.lightgbm.cache_seconds')));
                        $results[$group['id']] = ['units' => (float) $predictions[$key], 'reason' => null, 'version' => $metadata['model_version']];
                    }
                } catch (\Throwable $e) {
                    Log::warning('LightGBM unavailable', ['reason' => $e->getMessage(), 'horizon' => $job['horizon']]);
                    foreach ($groups as $group) {
                        $results[$group['id']] = ['units' => null, 'reason' => 'Prediksi LightGBM tidak tersedia: '.$e->getMessage(), 'version' => null];
                    }
                }
            }
        }

        return $results;
    }

    private function validUnits(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0;
    }

    private function validCached(mixed $cached, string $key, CarbonImmutable $end, array $job, array $metadata): bool
    {
        return is_array($cached) && ($cached['group_key'] ?? null) === $key && ($cached['as_of_date'] ?? null) === $end->toDateString()
            && ($cached['horizon_days'] ?? null) === $job['horizon'] && ($cached['forecast_use'] ?? null) === $job['use']
            && ($cached['model_version'] ?? null) === $metadata['model_version'] && $this->validUnits($cached['predicted_units'] ?? null);
    }
}
