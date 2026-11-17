<?php

namespace App\Services;

use App\Support\BranchCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LightgbmFeatureBuilder
{
    public function metadata(int $horizon, string $use): array
    {
        $path = config('forecast.lightgbm.model_dir').'/metadata_'.$horizon.'d.json';
        if (! is_file($path)) {
            throw new InvalidArgumentException("Model LightGBM untuk {$horizon} hari belum tersedia.");
        }
        $metadata = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (($metadata['schema_version'] ?? null) !== 'multihorizon-1' || ($metadata['horizon_days'] ?? null) !== $horizon || ! in_array($use, $metadata['forecast_uses'] ?? [], true)) {
            throw new InvalidArgumentException("Model LightGBM belum dilatih untuk {$use} {$horizon} hari.");
        }

        return $metadata;
    }

    public function context(array $groups, CarbonImmutable $end, int $horizon): array
    {
        $branches = array_values(array_unique(array_column($groups, 'branch_id')));
        $lastSales = DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')->join('barang as b', 'b.id', '=', 'i.product_id')
            ->where('s.status', 'valid')->whereIn('s.branch_id', $branches)->whereDate('s.tanggal', '<=', $end->toDateString())
            ->groupBy('s.branch_id', 'b.kategori_barang_id', 'b.kadar')->selectRaw('s.branch_id, b.kategori_barang_id as category_id, b.kadar, MAX(s.tanggal) as last_sale')->get();
        $last = [];
        foreach ($lastSales as $sale) {
            $last[DailySalesSeries::key($sale->branch_id, $sale->category_id, $sale->kadar)] = substr($sale->last_sale, 0, 10);
        }
        $calendar = [];
        foreach (DB::table('kalender_cabang')->whereIn('branch_id', $branches)->whereBetween('tanggal', [$end->addDay()->toDateString(), $end->addDays($horizon)->toDateString()])->get() as $day) {
            $calendar[$day->branch_id][$day->tanggal] = (bool) $day->buka;
        }

        return [
            'branch_codes' => DB::table('cabang')->whereIn('id', $branches)->pluck('kode_cabang', 'id')->all(),
            'last_sales' => $last,
            'calendar' => $calendar,
        ];
    }

    public function build(array $group, array $metadata, CarbonImmutable $end, array $context): array
    {
        $history = array_slice($group['values'], -$metadata['history_days']);
        if (count($history) !== $metadata['history_days'] || in_array(null, $history, true)) {
            throw new InvalidArgumentException('LightGBM memerlukan riwayat 90 hari yang lengkap.');
        }
        $branch = mb_strtoupper(trim($context['branch_codes'][$group['branch_id']] ?? ''));
        $category = mb_strtoupper(trim($group['kategori']));
        if (! isset($metadata['category_levels']['branch_code'][$branch], $metadata['category_levels']['category_code'][$category])) {
            throw new InvalidArgumentException('Cabang atau kategori belum didukung model LightGBM.');
        }
        if (! preg_match('/^(\d{1,2})K$/', DailySalesSeries::karat($group['kadar']), $match) || ! in_array((int) $match[1], $metadata['known_karat'], true)) {
            throw new InvalidArgumentException('Kadar belum didukung model LightGBM.');
        }
        $lastSale = $context['last_sales'][$group['key']] ?? null;
        $features = [
            'branch_code' => $metadata['category_levels']['branch_code'][$branch],
            'category_code' => $metadata['category_levels']['category_code'][$category],
            'karat' => (int) $match[1],
            'days_since_last_sale' => $lastSale === null ? null : (int) CarbonImmutable::parse($lastSale, 'Asia/Jakarta')->diffInDays($end),
        ];
        foreach ([7, 14, 30, 60, 90] as $days) {
            $features['sales_'.$days.'d'] = array_sum(array_slice($history, -$days));
        }
        $horizon = $metadata['horizon_days'];
        $open = $preEid = 0;
        $knownYears = array_unique(array_map(fn ($eid) => (int) substr($eid, 0, 4), BranchCalendar::EID_DATES));
        for ($offset = 1; $offset <= $horizon; $offset++) {
            $day = $end->addDays($offset);
            if (! in_array($day->year, $knownYears, true) || (BranchCalendar::EID_AVAILABLE_FROM[$day->year] ?? '0001-01-01') > $end->toDateString()) {
                throw new InvalidArgumentException('Kalender Idulfitri tahun '.$day->year.' belum tersedia untuk horizon ini.');
            }
            $open += (int) ($context['calendar'][$group['branch_id']][$day->toDateString()] ?? BranchCalendar::isOpen($branch, $day));
            foreach (BranchCalendar::EID_DATES as $eid) {
                $distance = (int) $day->diffInDays(CarbonImmutable::parse($eid, 'Asia/Jakarta'), false);
                if ($distance >= 1 && $distance <= 21) {
                    $preEid++;
                    break;
                }
            }
        }
        $features['open_days_next_'.$horizon] = $open;
        $features['pre_eid_days_next_'.$horizon] = $preEid;
        // ANTAM is intentionally not added to the selected calendar-only models.
        if (array_diff($metadata['feature_names'], array_keys($features))) {
            throw new InvalidArgumentException('Fitur model belum didukung pembentuk fitur Laravel.');
        }

        return array_replace(array_fill_keys($metadata['feature_names'], null), array_intersect_key($features, array_flip($metadata['feature_names'])));
    }
}
