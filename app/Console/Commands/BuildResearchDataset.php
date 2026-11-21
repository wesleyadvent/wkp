<?php

namespace App\Console\Commands;

use App\Support\BranchCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildResearchDataset extends Command
{
    protected $signature = 'data:build-forecast {--output= : Lokasi CSV hasil}';
    protected $description = 'Bangun dataset prediksi 14 hari dari transaksi valid dan kalender cabang';

    public function handle(): int
    {
        $start = CarbonImmutable::parse('2023-01-01', 'Asia/Jakarta');
        $end = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDay();
        $prices = DB::table('harga_antam_harian')->orderBy('tanggal')->get()->keyBy('tanggal');
        if ($prices->isEmpty()) { $this->error('Harga ANTAM belum tersedia.'); return self::FAILURE; }
        $shopPrices = [];
        foreach (DB::table('harga_emas')->orderBy('tanggal')->orderBy('id')->cursor() as $price) {
            $priceDate = CarbonImmutable::parse($price->tanggal, 'Asia/Jakarta')->toDateString();
            $shopPrices[$price->kadar][] = [$priceDate, $price->harga_per_gram];
        }
        $open = [];
        foreach (DB::table('kalender_cabang')->get() as $row) $open[$row->branch_id][$row->tanggal] = (bool) $row->buka;
        $path = $this->option('output') ?: storage_path('app/private/dataset-forecast.csv');
        $handle = fopen($path, 'w');
        if (! $handle) { $this->error('Tidak dapat menulis dataset.'); return self::FAILURE; }
        fputcsv($handle, ['tanggal_prediksi','kode_cabang','kategori','kadar','penjualan_hari_ini','total_7','total_14','total_30','total_60','total_90','hari_buka_14','jarak_idulfitri','h_minus_21_1_horizon','harga_antam','tanggal_sumber_harga','perubahan_harga_7_persen','harga_toko_per_gram','tanggal_harga_toko','target_14','akhir_target','split']);
        $count = 0;
        foreach (DB::table('cabang')->orderBy('id')->get() as $branch) {
            $series = [];
            $sales = DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')
                ->join('barang as b', 'b.id', '=', 'i.product_id')
                ->where('s.status', 'valid')->where('s.branch_id', $branch->id)
                ->groupBy('b.kategori_barang_id', 'b.kadar', 's.tanggal')
                ->selectRaw('b.kategori_barang_id, b.kadar, s.tanggal, COUNT(*) AS units')->cursor();
            foreach ($sales as $sale) $series[$sale->kategori_barang_id.'|'.$sale->kadar][$sale->tanggal] = (int) $sale->units;
            $calendarFeatures = [];
            for ($date = $start; $date <= $end; $date = $date->addDay()) {
                $futureOpen = 0; $preEid = 0; $distance = null;
                foreach (BranchCalendar::EID_DATES as $eid) {
                    $delta = (int) $date->diffInDays(CarbonImmutable::parse($eid, 'Asia/Jakarta'), false);
                    if ($distance === null || abs($delta) < abs($distance)) $distance = $delta;
                }
                for ($i = 1; $i <= 14; $i++) {
                    $future = $date->addDays($i);
                    $futureOpen += (int) ($open[$branch->id][$future->toDateString()] ?? BranchCalendar::isOpen($branch->kode_cabang, $future));
                    foreach (BranchCalendar::EID_DATES as $eid) {
                        $delta = (int) $future->diffInDays(CarbonImmutable::parse($eid, 'Asia/Jakarta'), false);
                        if ($delta >= 1 && $delta <= 21) { $preEid++; break; }
                    }
                }
                $calendarFeatures[$date->toDateString()] = [$futureOpen, $distance, $preEid];
            }
            foreach (DB::table('kategori_barang')->orderBy('id')->get() as $category) {
                for ($karat = 8; $karat <= 24; $karat++) {
                    $kadar = $karat.'K';
                    $daily = $series[$category->id.'|'.$kadar] ?? [];
                    $priceHistory = $shopPrices[$kadar] ?? [];
                    $priceIndex = 0;
                    $shopPrice = null;
                    $prefix = [0]; $dates = [];
                    for ($d = $start; $d <= $end; $d = $d->addDay()) {
                        $dates[] = $d;
                        $prefix[] = end($prefix) + ($daily[$d->toDateString()] ?? 0);
                    }
                    foreach ($dates as $index => $date) {
                        $key = $date->toDateString(); $p = $prices[$key] ?? null;
                        if (! $p) continue;
                        while (isset($priceHistory[$priceIndex]) && $priceHistory[$priceIndex][0] <= $key) {
                            $shopPrice = $priceHistory[$priceIndex++];
                        }
                        [$futureOpen, $distance, $preEid] = $calendarFeatures[$key];
                        $totals = [];
                        foreach ([7,14,30,60,90] as $window) $totals[] = $prefix[$index + 1] - $prefix[max(0, $index + 1 - $window)];
                        $targetEnd = $date->addDays(14);
                        $target = $targetEnd <= $end ? $prefix[$index + 15] - $prefix[$index + 1] : null;
                        $split = match (true) {
                            $date <= CarbonImmutable::parse('2024-12-31') && $targetEnd <= CarbonImmutable::parse('2024-12-31') => 'latih',
                            $date >= CarbonImmutable::parse('2025-01-01') && $targetEnd <= CarbonImmutable::parse('2025-09-30') => 'validasi',
                            $date >= CarbonImmutable::parse('2025-10-01') && $targetEnd <= CarbonImmutable::parse('2026-07-31') => 'uji',
                            default => null,
                        };
                        $old = $prices[$date->subDays(7)->toDateString()] ?? null;
                        fputcsv($handle, array_merge([$key,$branch->kode_cabang,$category->nama_kategori,$kadar,$daily[$key] ?? 0], $totals, [$futureOpen,$distance,$preEid,$p->harga,$p->tanggal_sumber,$old && $old->harga > 0 ? round(100 * ($p->harga / $old->harga - 1), 4) : null,$shopPrice[1] ?? null,$shopPrice[0] ?? null,$target,$targetEnd->toDateString(),$target === null ? null : $split]));
                        $count++;
                    }
                }
            }
        }
        fclose($handle);
        $this->info("Dataset: {$path}; {$count} baris.");
        return self::SUCCESS;
    }
}
