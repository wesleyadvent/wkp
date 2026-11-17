<?php

namespace App\Console\Commands;

use App\Services\DailySalesSeries;
use App\Services\LightgbmFeatureBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExportLightgbmDataset extends Command
{
    protected $signature = 'data:export-lightgbm {--from=} {--to=} {--horizon=14} {--branch=} {--output=}';

    protected $description = 'Ekspor panel harian dan fitur LightGBM yang sama dengan inferensi, tanpa data pelanggan';

    public function handle(DailySalesSeries $series, LightgbmFeatureBuilder $builder): int
    {
        $args = Validator::make($this->options(), [
            'from' => 'required|date_format:Y-m-d|before:today',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from|before:today',
            'horizon' => ['required', 'integer', Rule::in([3, 7, 14, 21, 30, 60, 90])],
            'branch' => 'nullable|integer|exists:cabang,id', 'output' => 'nullable|string',
        ])->validate();
        $horizon = (int) $args['horizon'];
        $metadata = $builder->metadata($horizon, in_array($horizon, [3, 7, 14, 21], true) ? 'lead_time' : 'projection');
        $path = $args['output'] ?? storage_path('app/private/dataset-lightgbm.csv');
        $handle = fopen($path, 'w');
        if (! $handle) {
            $this->error('Tidak dapat menulis dataset.');

            return self::FAILURE;
        }
        $lastCompleteDate = CarbonImmutable::now('Asia/Jakarta')->subDay()->startOfDay();
        $prices = DB::table('harga_antam_harian')->whereDate('tanggal', '<=', $args['to'])->orderBy('tanggal')->get();
        $priceAt = fn ($day) => $prices->last(fn ($price) => $price->tanggal <= $day && $price->tanggal_sumber <= $day);
        $known = [];
        foreach (DB::table('barang')->selectRaw('branch_id, kategori_barang_id as category_id, kadar, MIN(created_at) as first_date')->groupBy('branch_id', 'kategori_barang_id', 'kadar')->get() as $row) {
            $known[DailySalesSeries::key($row->branch_id, $row->category_id, $row->kadar)] = substr($row->first_date ?? '9999-12-31', 0, 10);
        }
        foreach (DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')->join('barang as b', 'b.id', '=', 'i.product_id')->where('s.status', 'valid')->groupBy('s.branch_id', 'b.kategori_barang_id', 'b.kadar')->selectRaw('s.branch_id, b.kategori_barang_id as category_id, b.kadar, MIN(s.tanggal) as first_date')->get() as $row) {
            $key = DailySalesSeries::key($row->branch_id, $row->category_id, $row->kadar);
            $known[$key] = min($known[$key] ?? '9999-12-31', substr($row->first_date, 0, 10));
        }
        $header = ['schema_version', 'as_of_date', 'target_end_date', 'group_key', ...$metadata['feature_names'], 'target_sales_'.$horizon.'d', 'feature_error',
            'tanggal_ramalan', 'kode_cabang', 'kategori', 'kadar', 'penjualan_hari_ini', 'harga_antam', 'perubahan_harga_7_persen', 'tanggal_sumber_harga', 'sumber_harga'];
        fputcsv($handle, $header, escape: '');
        $count = $missing = 0;
        try {
            for ($end = CarbonImmutable::parse($args['from'], 'Asia/Jakarta'); $end <= CarbonImmutable::parse($args['to'], 'Asia/Jakarta'); $end = $end->addDay()) {
                $groups = $series->groups($args['branch'] ? (int) $args['branch'] : null, $end);
                $context = $builder->context(array_values($groups), $end, $horizon);
                $targetEnd = $end->addDays($horizon);
                $future = $targetEnd <= $lastCompleteDate ? $series->groups($args['branch'] ? (int) $args['branch'] : null, $targetEnd, $horizon) : [];
                $price = $priceAt($end->toDateString());
                $old = $priceAt($end->subDays(7)->toDateString());
                foreach ($groups as $g) {
                    if (($known[$g['key']] ?? '9999-12-31') > $end->toDateString()) {
                        continue;
                    }
                    $error = null;
                    try {
                        $features = $builder->build($g, $metadata, $end, $context);
                    } catch (\InvalidArgumentException $e) {
                        $features = array_fill_keys($metadata['feature_names'], null);
                        $error = $e->getMessage();
                        $missing++;
                    }
                    $actual = $future[$g['key']]['values'] ?? [];
                    $target = count($actual) === $horizon && ! in_array(null, $actual, true) ? array_sum($actual) : null;
                    $row = ['schema_version' => $metadata['schema_version'], 'as_of_date' => $end->toDateString(), 'target_end_date' => $targetEnd->toDateString(), 'group_key' => $g['key'], ...$features,
                        'target_sales_'.$horizon.'d' => $target, 'feature_error' => $error,
                        'tanggal_ramalan' => $end->toDateString(), 'kode_cabang' => $context['branch_codes'][$g['branch_id']], 'kategori' => mb_strtoupper($g['kategori']), 'kadar' => $g['kadar'], 'penjualan_hari_ini' => $g['values'][array_key_last($g['values'])],
                        'harga_antam' => $price?->harga, 'perubahan_harga_7_persen' => $price && $old && $old->harga > 0 ? 100 * ($price->harga / $old->harga - 1) : null, 'tanggal_sumber_harga' => $price?->tanggal_sumber, 'sumber_harga' => $price?->sumber];
                    fputcsv($handle, array_map(fn ($name) => $row[$name] ?? null, $header), escape: '');
                    $count++;
                }
            }
        } finally {
            fclose($handle);
        }
        $this->info("Dataset: {$path}; {$count} baris; {$missing} baris fitur belum lengkap (ditandai, tidak diisi nol).");

        return self::SUCCESS;
    }
}
