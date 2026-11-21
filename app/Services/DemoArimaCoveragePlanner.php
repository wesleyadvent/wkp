<?php

namespace App\Services;

/** Plans additions to demo sales without changing observed transactions. */
class DemoArimaCoveragePlanner
{
    /**
     * @param  array<int, int|null>  $values  Daily unit sales, in calendar order.
     * @param  array<int, bool>  $open  Whether each calendar day is open.
     * @return array<int, int> Index => additional units to simulate.
     */
    public function plan(array $values, array $open, int $firstCutoff = 89, ?int $lastCutoff = null): array
    {
        $lastCutoff ??= count($values) - 1;
        if ($firstCutoff < 89 || $lastCutoff >= count($values) || $lastCutoff < $firstCutoff || count($open) !== count($values)) {
            throw new \InvalidArgumentException('Rentang harus memiliki minimal 90 hari riwayat dan kalender yang sesuai.');
        }

        $additions = [];
        for ($cutoff = $firstCutoff; $cutoff <= $lastCutoff; $cutoff++) {
            $left = max(0, $cutoff - 179);
            // Match the worker: only use observations after the last missing day.
            for ($i = $cutoff; $i >= $left; $i--) {
                if ($values[$i] === null) {
                    $left = $i + 1;
                    break;
                }
            }
            if ($cutoff - $left + 1 < 90) {
                throw new \RuntimeException('Pencatatan lengkap kurang dari 90 hari pada indeks '.$cutoff.'.');
            }

            $positive = count(array_filter(array_slice($values, $left, $cutoff - $left + 1), fn ($value) => $value > 0));
            // Prefer recent gaps: the new positive day remains useful for later cutoffs.
            for ($i = $cutoff; $positive < 14 && $i >= $left; $i--) {
                if ($values[$i] === 0 && $open[$i]) {
                    $values[$i] = 1;
                    $additions[$i] = 1;
                    $positive++;
                }
            }
            if ($positive < 14) {
                throw new \RuntimeException('Hari buka tidak cukup untuk 14 hari penjualan positif.');
            }

            $window = array_slice($values, $left, $cutoff - $left + 1);
            if (count(array_unique($window)) === 1) {
                for ($i = $cutoff; $i >= $left; $i--) {
                    if ($open[$i]) {
                        $values[$i]++;
                        $additions[$i] = ($additions[$i] ?? 0) + 1;
                        break;
                    }
                }
            }
        }

        ksort($additions);

        return $additions;
    }
}
