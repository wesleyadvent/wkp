<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DailySalesSeries
{
    public static function karat(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($value)));
    }

    public static function key(int $branch, int $category, string $karat): string
    {
        return $branch.'|'.$category.'|'.self::karat($karat);
    }

    public function groups(?int $branch, CarbonImmutable $end, int $days = 180): array
    {
        $start = $end->subDays($days - 1);
        $groups = [];
        $make = function ($row) use (&$groups): string {
            $key = self::key($row->branch_id, $row->category_id, $row->kadar);
            $groups[$key] ??= ['key' => $key, 'branch_id' => (int) $row->branch_id, 'category_id' => (int) $row->category_id, 'kadar' => self::karat($row->kadar), 'sales' => [], 'stok_tersedia' => 0];

            return $key;
        };
        $sales = DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')->join('barang as b', 'b.id', '=', 'i.product_id')
            ->where('s.status', 'valid')->whereBetween('s.tanggal', [$start->toDateString(), $end->toDateString()])->when($branch, fn ($q) => $q->where('s.branch_id', $branch))
            ->groupBy('s.branch_id', 'b.kategori_barang_id', 'b.kadar', 's.tanggal')->selectRaw('s.branch_id,b.kategori_barang_id as category_id,b.kadar,s.tanggal,COUNT(*) as units')->get();
        foreach ($sales as $row) {
            $key = $make($row);
            $groups[$key]['sales'][$row->tanggal] = ($groups[$key]['sales'][$row->tanggal] ?? 0) + (int) $row->units;
        }
        $stock = DB::table('barang')->where('status', 'tersedia')->whereNull('deleted_at')->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->groupBy('branch_id', 'kategori_barang_id', 'kadar')->selectRaw('branch_id,kategori_barang_id as category_id,kadar,COUNT(*) as units')->get();
        foreach ($stock as $row) {
            $key = $make($row);
            $groups[$key]['stok_tersedia'] += (int) $row->units;
        }
        $calendar = [];
        foreach (DB::table('kalender_cabang')->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->when($branch, fn ($q) => $q->where('branch_id', $branch))->get() as $day) {
            $calendar[$day->branch_id][$day->tanggal] = $day;
        }
        $branches = DB::table('cabang')->pluck('nama_cabang', 'id');
        $categories = DB::table('kategori_barang')->pluck('nama_kategori', 'id');
        foreach ($groups as &$group) {
            $group['cabang'] = $branches[$group['branch_id']];
            $group['kategori'] = $categories[$group['category_id']];
            $group['values'] = [];
            for ($date = $start; $date <= $end; $date = $date->addDay()) {
                $key = $date->toDateString();
                $record = $calendar[$group['branch_id']][$key] ?? null;
                $group['values'][] = $record && (! $record->buka || $record->pencatatan_lengkap) ? ($record->buka ? ($group['sales'][$key] ?? 0) : 0) : null;
            }
            $group['start'] = $start->toDateString();
            $group['cutoff'] = $end->toDateString();
            $group['input_hash'] = hash('sha256', json_encode([$group['key'], $group['start'], $group['cutoff'], $group['values']]));
        }
        unset($group);

        return $groups;
    }
}
