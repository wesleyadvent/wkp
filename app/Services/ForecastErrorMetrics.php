<?php

namespace App\Services;

use InvalidArgumentException;

class ForecastErrorMetrics
{
    private int $count = 0;

    private int $positiveCount = 0;

    private float $absolute = 0;

    private float $squared = 0;

    private float $percentage = 0;

    public function add(float $actual, float $predicted): void
    {
        if (! is_finite($actual) || ! is_finite($predicted) || $actual < 0 || $predicted < 0) {
            throw new InvalidArgumentException('Nilai aktual dan prediksi harus finite dan nonnegatif.');
        }
        $error = abs($predicted - $actual);
        $this->count++;
        $this->absolute += $error;
        $this->squared += $error * $error;
        if ($actual > 0) {
            $this->positiveCount++;
            $this->percentage += $error / $actual;
        }
    }

    public function result(): array
    {
        return [
            'n' => $this->count,
            'mae' => $this->count ? $this->absolute / $this->count : null,
            'rmse' => $this->count ? sqrt($this->squared / $this->count) : null,
            'mape' => $this->positiveCount ? 100 * $this->percentage / $this->positiveCount : null,
            'mape_n' => $this->positiveCount,
            'zero_actual' => $this->count - $this->positiveCount,
        ];
    }
}
