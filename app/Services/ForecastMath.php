<?php

namespace App\Services;

class ForecastMath
{
    public static function daily(array $values, string $method): ?float
    {
        if (! $values || in_array(null, $values, true)) {
            return null;
        }
        if ($method === 'SMA') {
            return array_sum($values) / count($values);
        }
        $sum = 0;
        foreach (array_values($values) as $i => $value) {
            $sum += ($i + 1) * $value;
        }

        return $sum / (count($values) * (count($values) + 1) / 2);
    }

    public static function status(int $stock, int $rop, int $quantity): string
    {
        if ($stock === 0 || ($rop > 0 && $quantity >= (int) ceil($rop * .5))) {
            return 'kritis';
        }

        return $stock <= $rop ? 'perlu_perhatian' : 'aman';
    }
}
