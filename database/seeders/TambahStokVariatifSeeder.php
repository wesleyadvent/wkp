<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TambahStokVariatifSeeder extends Seeder
{
    private const PERIOD_DAYS = 30;

    private const BATCH = 'STOK-SEIMBANG-20261003';

    /**
     * Menyeimbangkan stok setiap cabang, kategori, dan kadar berdasarkan ROP 30 hari.
     * Sekitar 50% kelompok ditargetkan aman, 30% perlu perhatian, dan 20% tetap
     * kritis agar halaman Analisis Persediaan nyaman tetapi tetap bermakna untuk demo.
     */
    public function run(): void
    {
        if (Barang::query()->where('creation_batch', self::BATCH)->exists()) {
            $this->command?->warn('Stok demo variatif sudah tersedia; tidak menambah duplikat.');

            return;
        }

        $end = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $start = $end->subDays(self::PERIOD_DAYS - 1);
        $prices = HargaEmas::query()
            ->where('tanggal', '<=', $end->toDateString())
            ->orderByDesc('tanggal')
            ->get()
            ->unique('kadar')
            ->mapWithKeys(fn (HargaEmas $price) => [$price->kadar => (int) $price->harga_per_gram]);
        $sales = DB::table('item_penjualan as item')
            ->join('penjualan as sale', 'sale.id', '=', 'item.sale_id')
            ->join('barang as product', 'product.id', '=', 'item.product_id')
            ->join('kategori_barang as category', 'category.id', '=', 'product.kategori_barang_id')
            ->where('sale.status', 'valid')
            ->whereBetween('sale.tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy('sale.branch_id', 'category.nama_kategori', 'product.kadar')
            ->selectRaw('sale.branch_id, category.nama_kategori as kategori, product.kadar, COUNT(item.id) as jumlah')
            ->get();
        $stocks = DB::table('barang as product')
            ->join('kategori_barang as category', 'category.id', '=', 'product.kategori_barang_id')
            ->where('status', 'tersedia')
            ->whereNull('deleted_at')
            ->groupBy('product.branch_id', 'category.nama_kategori', 'product.kadar')
            ->selectRaw('product.branch_id, category.nama_kategori as kategori, product.kadar, COUNT(*) as jumlah')
            ->get()
            ->keyBy(fn (object $row) => $this->groupKey((int) $row->branch_id, $row->kategori, $row->kadar));

        DB::transaction(function () use ($sales, $stocks, $prices): void {
            foreach ($sales as $group) {
                $branchId = (int) $group->branch_id;
                $category = $group->kategori;
                $karat = $group->kadar;
                $rop = (int) ceil((((int) $group->jumlah / self::PERIOD_DAYS) * 14) + 2);
                $stock = (int) ($stocks->get($this->groupKey($branchId, $category, $karat))->jumlah ?? 0);
                $target = $this->targetStock($branchId, $category, $karat, $rop);
                $addition = max(0, $target - $stock);

                for ($unit = 1; $unit <= $addition; $unit++) {
                    $seed = $branchId + $unit + (int) $group->jumlah + strlen($category) + (int) $karat;
                    $weight = round(1.0 + (($seed * 7) % 90) / 10, 3);
                    $makingCost = 100000 + (($seed % 6) * 25000);
                    $price = $prices[$karat] ?? 0;

                    Barang::create([
                        'creation_batch' => self::BATCH,
                        'branch_id' => $branchId,
                        'nama_barang' => $this->productName($category, $seed),
                        'kategori_barang_id' => KategoriBarang::where('nama_kategori', $category)->valueOrFail('id'),
                        'kadar' => $karat,
                        'berat' => $weight,
                        'harga_per_gram' => $price,
                        'ongkos' => $makingCost,
                        'total_harga' => round(($weight * $price) + $makingCost, 2),
                        'deskripsi' => 'Stok tambahan demo dengan tingkat persediaan bervariasi per cabang.',
                        'photo_path' => 'product-photos/demo-emas.png',
                        'status' => 'tersedia',
                    ]);
                }
            }
        });
    }

    private function targetStock(int $branchId, string $category, string $karat, int $rop): int
    {
        $selector = abs((int) crc32($branchId.'|'.$category.'|'.$karat)) % 10;

        return match (true) {
            // Di atas ROP dengan buffer yang terlihat jelas: aman.
            $selector < 5 => $rop + 3 + ($selector % 4),
            // Sekitar 20% di bawah ROP: perlu perhatian, tetapi belum kritis.
            $selector < 8 => max(1, $rop - max(1, (int) floor($rop * 0.2))),
            // Sekitar 60% di bawah ROP: tetap kritis sebagai contoh rekomendasi restock.
            default => max(0, $rop - (int) ceil($rop * 0.6)),
        };
    }

    private function groupKey(int $branchId, string $category, string $karat): string
    {
        return implode("\x1F", [$branchId, $category, $karat]);
    }

    private function productName(string $category, int $seed): string
    {
        $names = [
            'Anting' => ['Anting Viola', 'Anting Gipsi', 'Anting Cor', 'Anting Bali'],
            'Cincin' => ['Cincin Cor', 'Cincin Bunton', 'Cincin Ukir', 'Cincin Solitaire'],
            'Kalung' => ['Kalung Viola', 'Kalung Cor', 'Kalung Rantai', 'Kalung Italia'],
            'Gelang' => ['Gelang Anak', 'Gelang Rantai', 'Gelang Plat', 'Gelang Bola'],
            'Liontin' => ['Liontin Hati', 'Liontin Huruf', 'Liontin Klasik', 'Liontin Berlian'],
        ];

        return $names[$category][$seed % count($names[$category])];
    }
}
