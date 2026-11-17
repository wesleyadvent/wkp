<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\ItemPenjualan;
use App\Models\KategoriBarang;
use App\Models\Karyawan;
use App\Models\KonfigurasiPersediaan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAnalisisPersediaanSeeder extends Seeder
{
    /**
     * Data demonstrasi idempoten untuk analisis persediaan.
     * Kode barang dan nomor nota diawali DEMO-ANL agar mudah dikenali.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $branches = collect([
                ['kode_cabang' => 'WKP-PWG', 'nama_cabang' => 'WKP Purwonegoro', 'alamat' => 'Jl. Raya Purwonegoro No. 12, Banjarnegara', 'telepon' => '0286-591001'],
                ['kode_cabang' => 'WKP-SPH', 'nama_cabang' => 'WKP Sumpiuh', 'alamat' => 'Jl. Nasional III No. 24, Sumpiuh, Banyumas', 'telepon' => '0282-497001'],
                ['kode_cabang' => 'WKP-MDR', 'nama_cabang' => 'WKP Mandiraja', 'alamat' => 'Jl. Mandiraja Raya No. 8, Banjarnegara', 'telepon' => '0286-411001'],
                ['kode_cabang' => 'WKP-DSA', 'nama_cabang' => 'WKP Desa', 'alamat' => 'Jl. Pasar Desa No. 5, Banjarnegara', 'telepon' => '0286-521001'],
            ])->map(fn (array $branch) => Cabang::firstOrCreate(
                ['kode_cabang' => $branch['kode_cabang']],
                $branch + ['status' => 'aktif', 'is_login_enabled' => true],
            ));

            $prices = ['24K' => 1250000, '22K' => 1125000, '18K' => 925000];
            foreach ($prices as $karat => $price) {
                HargaEmas::firstOrCreate(
                    ['tanggal' => now()->toDateString(), 'kadar' => $karat],
                    ['harga_per_gram' => $price, 'keterangan' => 'Harga data demonstrasi analisis persediaan'],
                );
            }

            foreach (['Anting', 'Cincin', 'Kalung', 'Gelang'] as $category) {
                KategoriBarang::firstOrCreate(
                    ['nama_kategori' => $category],
                    ['keterangan' => 'Kategori data demonstrasi analisis persediaan', 'is_active' => true],
                );
            }

            $employee = Karyawan::firstOrCreate(['nama' => 'Karyawan Demo Analisis'], ['is_active' => true]);
            $catalog = [
                ['name' => 'Anting Viola', 'category' => 'Anting', 'weight' => 1.20],
                ['name' => 'Anting Gipsi', 'category' => 'Anting', 'weight' => 1.50],
                ['name' => 'Anting Cor', 'category' => 'Anting', 'weight' => 1.80],
                ['name' => 'Cincin Cor', 'category' => 'Cincin', 'weight' => 2.20],
                ['name' => 'Cincin Anak', 'category' => 'Cincin', 'weight' => 1.10],
                ['name' => 'Cincin Bunton', 'category' => 'Cincin', 'weight' => 2.70],
                ['name' => 'Kalung Cor', 'category' => 'Kalung', 'weight' => 4.50],
                ['name' => 'Kalung Viola', 'category' => 'Kalung', 'weight' => 3.80],
                ['name' => 'Gelang Anak', 'category' => 'Gelang', 'weight' => 3.10],
                ['name' => 'Gelang Rantai', 'category' => 'Gelang', 'weight' => 5.20],
                ['name' => 'Gelang Plat', 'category' => 'Gelang', 'weight' => 6.40],
            ];
            $patterns = [
                ['current' => 6, 'previous' => 0],
                ['current' => 6, 'previous' => 2],
                ['current' => 4, 'previous' => 4],
                ['current' => 2, 'previous' => 5],
                ['current' => 7, 'previous' => 3],
            ];

            foreach ($branches->values() as $branchIndex => $branch) {
                $customers = collect(range(1, 12))->map(function (int $number) use ($branch): Pelanggan {
                    return Pelanggan::firstOrCreate(
                        ['branch_id' => $branch->id, 'telepon' => '0817'.str_pad((string) ($branch->id * 100 + $number), 8, '0', STR_PAD_LEFT)],
                        [
                            'nama' => 'Pelanggan Demo '.$branch->kode_cabang.' '.$number,
                            'alamat' => 'Alamat contoh pelanggan '.$number.' cabang '.$branch->nama_cabang,
                        ],
                    );
                });

                foreach ($catalog as $productIndex => $product) {
                    foreach (array_keys($prices) as $karatIndex => $karat) {
                        $price = $prices[$karat];
                        $stockCount = 3 + (($branchIndex + $productIndex + $karatIndex) % 3);
                        for ($unit = 1; $unit <= $stockCount; $unit++) {
                            $weight = $product['weight'] + ((($unit + $productIndex) % 3) * 0.10);
                            $this->product(
                                'DEMO-ANL-STK-'.$branch->kode_cabang.'-'.($productIndex + 1).'-'.($karatIndex + 1).'-'.$unit,
                                $branch,
                                $product,
                                $karat,
                                $price,
                                $weight,
                                'tersedia',
                            );
                        }
                    }

                    $pattern = $patterns[($branchIndex + $productIndex) % count($patterns)];
                    $karatKeys = array_keys($prices);
                    $karat = $karatKeys[($branchIndex + $productIndex) % count($karatKeys)];
                    $price = $prices[$karat];

                    for ($number = 1; $number <= $pattern['current']; $number++) {
                        $this->createSale(
                            $branch,
                            $customers->get(($productIndex + $number) % $customers->count()),
                            $employee,
                            $product,
                            $karat,
                            $price,
                            now()->startOfDay()->subDays(($number * 2 + $productIndex + $branchIndex) % 29),
                            'valid',
                            'C'.$number,
                            $productIndex,
                        );
                    }

                    for ($number = 1; $number <= $pattern['previous']; $number++) {
                        $this->createSale(
                            $branch,
                            $customers->get(($productIndex + $number + 3) % $customers->count()),
                            $employee,
                            $product,
                            $karat,
                            $price,
                            now()->startOfDay()->subDays(30 + (($number * 3 + $productIndex + $branchIndex) % 30)),
                            'valid',
                            'P'.$number,
                            $productIndex,
                        );
                    }

                    if ($productIndex % 2 === 0) {
                        $this->createSale(
                            $branch,
                            $customers->get($productIndex % $customers->count()),
                            $employee,
                            $product,
                            $karat,
                            $price,
                            now()->startOfDay()->subDays($productIndex % 14),
                            'batal',
                            'B1',
                            $productIndex,
                        );
                    }
                }
            }

            foreach ([
                [$branches->get(0), 'Cincin', '24K', 14, 4],
                [$branches->get(1), 'Anting', '22K', 14, 3],
                [$branches->get(2), 'Kalung', '18K', 14, 5],
                [$branches->get(3), 'Gelang', '24K', 14, 6],
            ] as [$branch, $category, $karat, $leadTime, $safetyStock]) {
                KonfigurasiPersediaan::updateOrCreate(
                    ['branch_id' => $branch->id, 'kategori' => $category, 'kadar' => $karat],
                    ['lead_time_hari' => $leadTime, 'safety_stock' => $safetyStock],
                );
            }
        });
    }

    /** @param array{name:string,category:string,weight:float} $product */
    private function product(string $code, Cabang $branch, array $product, string $karat, int $price, float $weight, string $status): Barang
    {
        $makingCost = (int) round($weight * 125000);

        return Barang::updateOrCreate(
            ['kode_barang' => $code],
            [
                'branch_id' => $branch->id,
                'nama_barang' => $product['name'],
                'kategori_barang_id' => KategoriBarang::where('nama_kategori', $product['category'])->valueOrFail('id'),
                'kadar' => $karat,
                'berat' => $weight,
                'harga_per_gram' => $price,
                'ongkos' => $makingCost,
                'total_harga' => ($weight * $price) + $makingCost,
                'deskripsi' => 'Data demonstrasi untuk analisis tren penjualan dan rekomendasi persediaan.',
                'status' => $status,
            ],
        );
    }

    /** @param array{name:string,category:string,weight:float} $product */
    private function createSale(Cabang $branch, Pelanggan $customer, Karyawan $employee, array $product, string $karat, int $price, $date, string $status, string $periodCode, int $productIndex): void
    {
        $code = 'DEMO-ANL-SAL-'.$branch->kode_cabang.'-'.($productIndex + 1).'-'.$periodCode;
        $weight = $product['weight'] + ((($productIndex + strlen($periodCode)) % 3) * 0.10);
        $soldProduct = $this->product($code, $branch, $product, $karat, $price, $weight, 'terjual');
        $subtotal = ($weight * $price) + $soldProduct->ongkos;
        $sale = Penjualan::updateOrCreate(
            ['nomor_nota' => $code],
            [
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->nama,
                'tanggal' => $date->toDateString(),
                'total_berat' => $weight,
                'total_ongkos' => $soldProduct->ongkos,
                'total_harga' => $subtotal,
                'status' => $status,
            ],
        );

        ItemPenjualan::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $soldProduct->id],
            [
                'berat' => $weight,
                'harga_per_gram' => $price,
                'ongkos' => $soldProduct->ongkos,
                'subtotal' => $subtotal,
            ],
        );
    }
}
