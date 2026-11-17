<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\JenisLayanan;
use App\Models\JenisPotongan;
use App\Models\Karyawan;
use App\Models\Pengguna;
use App\Services\ReceiptSnapshot;
use App\Support\BranchCalendar;
use App\Support\ProductCode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DemoOperasionalSeeder extends Seeder
{
    private int $days;

    private array $antam = [];

    private const AVAILABLE_PRODUCTS_PER_BRANCH = 600;

    /**
     * Mengisi ulang data operasional untuk kebutuhan demo.
     *
     * Setiap cabang memiliki 500 pelanggan, 600 stok tersedia, minimal 50
     * penjualan valid/hari, 30 pembelian/hari, dan 5 nota service/hari pada
     * rentang 365 hari terakhir. Barang yang terjual tetap dicatat sebagai
     * unit terpisah agar riwayat penjualan dan analisis persediaan konsisten.
     */
    public function run(): void
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDay();
        $this->days = max(365, (int) env('DEMO_HISTORY_DAYS', 365));

        DB::transaction(function () use ($today): void {
            $this->clearOperationalData();
            $photoPath = $this->prepareProductPhoto();
            $branches = Cabang::query()->orderBy('id')->get();

            if ($branches->isEmpty()) {
                throw new \RuntimeException('Data cabang belum tersedia. Jalankan DatabaseSeeder terlebih dahulu.');
            }

            [$categories, $prices] = $this->seedMasters($today);
            $calendar = [];
            foreach ($branches as $branch) {
                for ($i = $this->days - 1; $i >= -30; $i--) {
                    $date = $today->subDays($i);
                    // Dataset demo beroperasi setiap hari agar minimum transaksi
                    // per hari terpenuhi dan seri prediksi tidak kehilangan observasi.
                    $calendar[] = ['branch_id' => $branch->id, 'tanggal' => $date->toDateString(), 'buka' => true, 'pencatatan_lengkap' => $i >= 0 ? true : null];
                }
            }
            $this->insertChunks('kalender_cabang', $calendar);
            $deductionTypeId = (int) JenisPotongan::firstOrCreate(
                ['nama_potongan' => 'Potongan Kadar'],
                ['nominal' => 0, 'keterangan' => 'Penyesuaian kadar emas pembelian', 'is_active' => true],
            )->id;

            $serviceTypes = collect([
                ['nama_service' => 'Cuci Emas', 'harga_default' => 25000],
                ['nama_service' => 'Poles Emas', 'harga_default' => 45000],
                ['nama_service' => 'Solder Rantai', 'harga_default' => 85000],
                ['nama_service' => 'Ubah Ukuran Cincin', 'harga_default' => 100000],
                ['nama_service' => 'Perbaikan Pengait', 'harga_default' => 65000],
            ])->map(fn (array $type) => JenisLayanan::updateOrCreate(
                ['nama_service' => $type['nama_service']],
                $type + ['keterangan' => 'Layanan demo operasional', 'is_active' => true],
            ));

            foreach ($branches as $branch) {
                $employee = Karyawan::firstOrCreate(
                    ['nama' => 'Petugas '.$branch->nama_cabang],
                    ['is_active' => true],
                );
                $operatorId = Pengguna::query()
                    ->where('branch_id', $branch->id)
                    ->where('role', 'toko_cabang')
                    ->value('id');
                $customerIds = $this->seedCustomers($branch, $operatorId, $today);

                $this->seedSalesAndProducts(
                    $branch,
                    $customerIds,
                    $employee->id,
                    $employee->nama,
                    $operatorId,
                    $categories,
                    $prices,
                    $photoPath,
                    $today,
                );
                $this->seedInventoryConfigurations($branch, $categories, $today);
                $this->seedPurchases(
                    $branch,
                    $customerIds,
                    $employee->id,
                    $employee->nama,
                    $operatorId,
                    $categories,
                    $prices,
                    $deductionTypeId,
                    $today,
                );
                $this->seedServiceNotes(
                    $branch,
                    $customerIds,
                    $employee->id,
                    $employee->nama,
                    $operatorId,
                    $categories,
                    $serviceTypes,
                    $today,
                );
                $this->command?->info($branch->nama_cabang.': stok, pelanggan, penjualan, pembelian, service dan konfigurasi selesai.');
            }

            $this->seedNoteSequences();
            $this->command?->info('Menyimpan snapshot nota historis...');
            $this->backfillReceiptSnapshots();
        });
    }

    private function clearOperationalData(): void
    {
        // Hapus anak relasi terlebih dahulu agar seluruh foreign key tetap aman.
        DB::table('pembatalan_transaksi')->delete();
        DB::table('inventory_forecasts')->delete();
        DB::table('urutan_nomor_nota')->delete();
        DB::table('kalender_cabang')->delete();
        DB::table('harga_antam_harian')->delete();
        DB::table('item_nota_layanan')->delete();
        DB::table('nota_layanan')->delete();
        DB::table('potongan_pembelian')->delete();
        DB::table('item_pembelian')->delete();
        DB::table('pembelian')->delete();
        DB::table('item_penjualan')->delete();
        DB::table('penjualan')->delete();
        DB::table('konfigurasi_persediaan')->delete();
        DB::table('barang')->delete();
        DB::table('pelanggan')->delete();
        DB::table('harga_emas')->delete();
        DB::table('urutan_kode_barang')->delete();
        DB::table('kategori_barang')->delete();
        DB::table('urutan_harian_barang')->delete();
    }

    private function prepareProductPhoto(): string
    {
        $source = base_path('docs/image.png');

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas foto demo docs/image.png tidak ditemukan.');
        }

        $path = 'product-photos/perhiasan-emas.png';
        Storage::disk('public')->put($path, File::get($source));

        return $path;
    }

    /** @return array{0: array<int, string>, 1: array<string, int>} */
    private function seedMasters(CarbonImmutable $today): array
    {
        $categories = ['Anting', 'Cincin', 'Kalung', 'Gelang', 'Liontin'];
        foreach ($categories as $category) {
            DB::table('kategori_barang')->insert([
                'nama_kategori' => $category,
                'keterangan' => 'Koleksi perhiasan emas '.$category,
                'is_active' => true,
                'created_at' => $today,
                'updated_at' => $today,
            ]);
        }

        $file = fopen(database_path('seeders/antam_historical_gold_prices.csv'), 'r');
        if (! $file) {
            throw new \RuntimeException('Sumber harga ANTAM tidak ditemukan.');
        }
        fgetcsv($file);
        $observed = [];
        while (($line = fgetcsv($file)) !== false) {
            if (count($line) >= 3 && is_numeric($line[1])) {
                $observed[substr($line[2], 0, 10)] = (float) $line[1];
            }
        }
        fclose($file);
        // Harga 1 gram dilaporkan oleh ANTARA untuk 12 November 2026.
        $observed['2026-11-12'] = 2518000;
        $observed['2026-11-13'] = 2509000;
        $observed['2026-11-14'] = 2517000;
        ksort($observed);
        $lastPrice = null;
        $lastDate = null;
        $antamRows = [];
        foreach ($observed as $date => $value) {
            if ($date < $today->subDays($this->days - 1)->toDateString()) {
                $lastPrice = $value;
                $lastDate = $date;
            }
        }
        $prices = [];
        $priceRows = [];
        for ($dayOffset = $this->days - 1; $dayOffset >= 0; $dayOffset--) {
            $date = $today->subDays($dayOffset);
            $key = $date->toDateString();
            if (isset($observed[$key])) {
                $lastPrice = $observed[$key];
                $lastDate = $key;
            }
            if ($lastPrice === null) {
                throw new \RuntimeException('Harga ANTAM awal tidak tersedia.');
            }
            $reference24K = $lastPrice;
            $this->antam[$key] = $reference24K;
            $antamRows[] = ['tanggal' => $key, 'harga' => $reference24K, 'tanggal_sumber' => $lastDate, 'sumber' => $lastDate >= '2026-11-12' ? 'ANTARA Logam Mulia '.$lastDate : 'theonegareth/antam_historical_gold_prices'];
            for ($karat = 8; $karat <= 24; $karat++) {
                $price = round($reference24K * $karat / 24, 2);
                if ($dayOffset === 0) {
                    $prices[$karat.'K'] = $price;
                }
                $priceRows[] = [
                    'tanggal' => $date->toDateString(),
                    'kadar' => $karat.'K',
                    'harga_per_gram' => $price,
                    'keterangan' => 'Harga acuan simulasi; sumber ANTAM '.$lastDate,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
        }
        $currentDate = $today->addDay();
        $currentKey = $currentDate->toDateString();
        if (isset($observed[$currentKey])) {
            $lastPrice = $observed[$currentKey];
            $lastDate = $currentKey;
        }
        $antamRows[] = ['tanggal' => $currentKey, 'harga' => $lastPrice, 'tanggal_sumber' => $lastDate, 'sumber' => $lastDate === '2026-11-14' ? 'Logam Mulia via Liputan6, 14 November 2026' : ($lastDate >= '2026-11-12' ? 'ANTARA Logam Mulia '.$lastDate : 'theonegareth/antam_historical_gold_prices')];
        for ($karat = 8; $karat <= 24; $karat++) {
            $prices[$karat.'K'] = round($lastPrice * $karat / 24, 2);
            $priceRows[] = ['tanggal' => $currentKey, 'kadar' => $karat.'K', 'harga_per_gram' => $prices[$karat.'K'], 'keterangan' => 'Harga acuan simulasi; sumber ANTAM '.$lastDate, 'created_at' => $currentDate, 'updated_at' => $currentDate];
        }
        $this->insertChunks('harga_antam_harian', $antamRows);
        $this->insertChunks('harga_emas', $priceRows);

        return [$categories, $prices];
    }

    /** @return array<int, int> */
    private function seedCustomers(Cabang $branch, ?int $operatorId, CarbonImmutable $today): array
    {
        $firstNames = ['Aditya', 'Agus', 'Aisyah', 'Andi', 'Anita', 'Arif', 'Aulia', 'Bayu', 'Cahya', 'Dewi', 'Dimas', 'Dinda', 'Eka', 'Fajar', 'Farah', 'Fitri', 'Galih', 'Hana', 'Indra', 'Intan', 'Joko', 'Kurnia', 'Laras', 'Linda', 'Maya', 'Mila', 'Nanda', 'Nia', 'Nur', 'Putra', 'Rani', 'Ratih', 'Reza', 'Rizky', 'Sari', 'Sinta', 'Sri', 'Taufik', 'Tika', 'Wahyu'];
        $lastNames = ['Pratama', 'Lestari', 'Saputra', 'Wati', 'Hidayat', 'Permata', 'Setiawan', 'Kusuma', 'Handayani', 'Wijaya', 'Puspita', 'Ramadhan', 'Nugroho'];
        $streets = ['Jl. Sudirman', 'Jl. Pemuda', 'Jl. Diponegoro', 'Jl. Ahmad Yani', 'Jl. Veteran', 'Jl. Gajah Mada'];
        $rows = [];

        for ($number = 1; $number <= 500; $number++) {
            $rows[] = [
                'nama' => $firstNames[($number - 1) % count($firstNames)].' '.$lastNames[intdiv($number - 1, count($firstNames)) % count($lastNames)],
                'alamat' => $streets[$number % count($streets)].' No. '.(10 + (($number * 7) % 190)).', '.$branch->nama_cabang,
                'telepon' => sprintf('0812%03d%05d', $branch->id, $number),
                'created_by' => $operatorId,
                'branch_id' => $branch->id,
                'created_at' => $today->subDays($this->days + 30)->setTime(8, $number % 60),
                'updated_at' => $today,
            ];
        }

        $this->insertChunks('pelanggan', $rows);

        return DB::table('pelanggan')->where('branch_id', $branch->id)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @param array<int, string> $categories @param array<string, int> $prices @param array<int, int> $customerIds */
    private function seedSalesAndProducts(Cabang $branch, array $customerIds, int $employeeId, string $employeeName, ?int $operatorId, array $categories, array $prices, string $photoPath, CarbonImmutable $today): void
    {
        $products = [];
        $sales = [];
        $itemReferences = [];
        $sequenceRows = [];
        $saleNumber = 1;
        $categoryIds = DB::table('kategori_barang')->pluck('id', 'nama_kategori')->map(fn ($id) => (int) $id)->all();
        $codeCounters = array_fill_keys($categories, 0);

        for ($dayOffset = $this->days - 1; $dayOffset >= 0; $dayOffset--) {
            $date = $today->subDays($dayOffset);
            $weekend = $date->isWeekend() ? 8 : 0;
            $payday = $date->day >= 25 || $date->day <= 5 ? 6 : 0;
            $trend = (int) floor(8 * ($this->days - 1 - $dayOffset) / $this->days);
            $validSales = 50 + (abs(crc32($branch->kode_cabang.$date->toDateString())) % 19) + $weekend + $payday + $trend + $this->eidIncrease($branch, $date);
            $dailyPrices = $this->pricesFor($date);
            for ($slot = 1; $slot <= $validSales; $slot++) {
                $seed = ($dayOffset * 67) + ($slot * 17) + ($branch->id * 11);
                [$demandCategory, $demandKarat] = $this->demandMix($branch, $date, $slot, $dayOffset);
                [$category, $name, $karat, $weight, $price, $makingCost] = $this->productDetail($seed, $categories, $dailyPrices, $dayOffset, $demandCategory, $demandKarat);
                $code = $this->productCode($branch, $category, ++$codeCounters[$category]);
                $timestamp = $date->setTime(9 + ($slot % 8), ($slot * 7) % 60);
                $subtotal = $this->money(($weight * $price) + $makingCost);
                $status = $slot <= $validSales ? 'valid' : 'batal';
                $note = sprintf('PJL/%s/%s/%04d', $branch->kode_cabang, $date->format('Y'), $saleNumber++);

                $products[] = $this->productRow($code, $branch->id, $name, $category, $categoryIds[$category], $karat, $weight, $price, $makingCost, $subtotal, $photoPath, 'terjual', $timestamp);
                $sales[] = [
                    'nomor_nota' => $note,
                    'branch_id' => $branch->id,
                    'customer_id' => $customerIds[$seed % count($customerIds)],
                    'tanggal' => $date->toDateString(),
                    'total_berat' => $weight,
                    'total_ongkos' => $makingCost,
                    'total_harga' => $subtotal,
                    'status' => $status,
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'created_by' => $operatorId,
                    'cancelled_by' => $status === 'batal' ? $operatorId : null,
                    'cancelled_at' => $status === 'batal' ? $timestamp->addHours(2) : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $itemReferences[] = compact('note', 'code', 'weight', 'price', 'makingCost', 'subtotal', 'timestamp');
            }
            if (count($sales) >= 2000) {
                $this->insertSalesBatch($products, $sales, $itemReferences);
                $products = [];
                $sales = [];
                $itemReferences = [];
            }
        }
        $this->insertSalesBatch($products, $sales, $itemReferences);
        $products = [];
        $sales = [];
        $itemReferences = [];

        // Stok aktif tepat 600 unit/cabang, sengaja tidak merata agar MA dan ROP
        // menghasilkan kombinasi aman, perlu perhatian, dan kritis.
        $stockNumber = 0;
        foreach ($this->stockTargets($branch, $categories) as $target) {
            for ($unit = 1; $unit <= $target['quantity']; $unit++) {
                $stockNumber++;
                $seed = ($this->days * 101) + ($stockNumber * 23) + ($branch->id * 13);
                [$category, $name, $karat, $weight, $price, $makingCost] = $this->productDetail(
                    $seed,
                    $categories,
                    $prices,
                    0,
                    $target['category'],
                    $target['karat'],
                );
                $products[] = $this->productRow(
                    $this->productCode($branch, $category, ++$codeCounters[$category]),
                    $branch->id,
                    $name,
                    $category,
                    $categoryIds[$category],
                    $karat,
                    $weight,
                    $price,
                    $makingCost,
                    $this->money(($weight * $price) + $makingCost),
                    $photoPath,
                    'tersedia',
                    $today->subDays(1 + ($seed % 45))->setTime(10 + ($stockNumber % 8), ($stockNumber * 3) % 60),
                );
            }
        }
        foreach ($codeCounters as $category => $lastNumber) {
            $sequenceRows[] = $this->sequenceRow($branch->id, $categoryIds[$category], $lastNumber);
        }

        $this->insertChunks('barang', $products);
        $this->insertChunks('urutan_kode_barang', $sequenceRows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, array<string, mixed>>  $sales
     * @param  array<int, array<string, mixed>>  $itemReferences
     */
    private function insertSalesBatch(array $products, array $sales, array $itemReferences): void
    {
        if ($sales === []) {
            return;
        }

        $this->insertChunks('barang', $products);
        $this->insertChunks('penjualan', $sales);
        $productIds = $this->idsByValues('barang', 'kode_barang', array_column($itemReferences, 'code'));
        $saleIds = $this->idsByValues('penjualan', 'nomor_nota', array_column($itemReferences, 'note'));
        $items = [];
        foreach ($itemReferences as $reference) {
            $items[] = [
                'sale_id' => $saleIds[$reference['note']],
                'product_id' => $productIds[$reference['code']],
                'berat' => $reference['weight'],
                'harga_per_gram' => $reference['price'],
                'ongkos' => $reference['makingCost'],
                'subtotal' => $reference['subtotal'],
                'created_at' => $reference['timestamp'],
                'updated_at' => $reference['timestamp'],
            ];
        }
        $this->insertChunks('item_penjualan', $items);
    }

    /** @param array<int, int> $customerIds @param array<int, string> $categories @param array<string, int> $prices */
    private function seedPurchases(Cabang $branch, array $customerIds, int $employeeId, string $employeeName, ?int $operatorId, array $categories, array $prices, int $deductionTypeId, CarbonImmutable $today): void
    {
        $purchases = [];
        $itemReferences = [];
        $purchaseNumber = 1;

        for ($dayOffset = $this->days - 1; $dayOffset >= 0; $dayOffset--) {
            $date = $today->subDays($dayOffset);
            $dailyCount = 30 + abs(crc32('beli'.$branch->kode_cabang.$date->toDateString())) % 7;
            $dailyPrices = $this->pricesFor($date);
            for ($slot = 1; $slot <= $dailyCount; $slot++) {
                $seed = ($dayOffset * 43) + ($slot * 19) + ($branch->id * 7);
                $category = $categories[$seed % count($categories)];
                $karat = (($seed % 17) + 8).'K';
                $weight = $this->weightFor($category, $seed);
                $marketPrice = $dailyPrices[$karat];
                $buyPrice = (int) (round(($marketPrice * (82 + ($seed % 8))) / 100) / 1000) * 1000;
                $cutPerGram = $slot % 4 === 0 ? 10000 : ($slot % 3 === 0 ? 5000 : 0);
                $extraCut = $slot % 5 === 0 ? 25000 : 0;
                $subtotal = $this->money($weight * max(0, $buyPrice - $cutPerGram));
                $timestamp = $date->setTime(11 + ($slot % 7), ($slot * 5) % 60);
                $status = $slot > $dailyCount ? 'batal' : 'valid';
                $note = sprintf('PBL/%s/%s/%04d', $branch->kode_cabang, $date->format('Y'), $purchaseNumber++);

                $purchases[] = [
                    'nomor_nota' => $note,
                    'branch_id' => $branch->id,
                    'customer_id' => $customerIds[$seed % count($customerIds)],
                    'sumber_barang' => 'barang_luar',
                    'sale_id_ref' => null,
                    'tanggal' => $date->toDateString(),
                    'total_berat' => $weight,
                    'total_potongan' => $this->money(($weight * $cutPerGram) + $extraCut),
                    'total_harga' => max(0, $subtotal - $extraCut),
                    'status' => $status,
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'created_by' => $operatorId,
                    'cancelled_by' => $status === 'batal' ? $operatorId : null,
                    'cancelled_at' => $status === 'batal' ? $timestamp->addHour() : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $itemReferences[] = compact('note', 'category', 'karat', 'weight', 'buyPrice', 'cutPerGram', 'subtotal', 'extraCut', 'timestamp');
            }
            if (count($purchases) >= 2000) {
                $this->insertPurchaseBatch($purchases, $itemReferences, $deductionTypeId);
                $purchases = [];
                $itemReferences = [];
            }
        }
        $this->insertPurchaseBatch($purchases, $itemReferences, $deductionTypeId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $purchases
     * @param  array<int, array<string, mixed>>  $itemReferences
     */
    private function insertPurchaseBatch(array $purchases, array $itemReferences, int $deductionTypeId): void
    {
        if ($purchases === []) {
            return;
        }
        $this->insertChunks('pembelian', $purchases);
        $purchaseIds = $this->idsByValues('pembelian', 'nomor_nota', array_column($itemReferences, 'note'));
        $items = [];
        $deductions = [];
        foreach ($itemReferences as $reference) {
            $purchaseId = $purchaseIds[$reference['note']];
            $items[] = [
                'purchase_id' => $purchaseId,
                'product_id_ref' => null,
                'nama_barang' => $reference['category'].' Emas Bekas',
                'kategori' => $reference['category'],
                'kadar' => $reference['karat'],
                'berat' => $reference['weight'],
                'harga_per_gram_beli' => $reference['buyPrice'],
                'potongan_per_gram' => $reference['cutPerGram'],
                'subtotal' => $reference['subtotal'],
                'created_at' => $reference['timestamp'],
                'updated_at' => $reference['timestamp'],
            ];
            if ($reference['extraCut'] > 0) {
                $deductions[] = [
                    'purchase_id' => $purchaseId,
                    'deduction_type_id' => $deductionTypeId,
                    'nominal' => $reference['extraCut'],
                    'keterangan' => 'Penyesuaian kondisi fisik perhiasan',
                    'created_at' => $reference['timestamp'],
                    'updated_at' => $reference['timestamp'],
                ];
            }
        }
        $this->insertChunks('item_pembelian', $items);
        $this->insertChunks('potongan_pembelian', $deductions);
    }

    /** @param array<int, int> $customerIds @param array<int, string> $categories */
    private function seedServiceNotes(Cabang $branch, array $customerIds, int $employeeId, string $employeeName, ?int $operatorId, array $categories, $serviceTypes, CarbonImmutable $today): void
    {
        $notes = [];
        $itemReferences = [];
        $serviceNumber = 1;
        $serviceTypes = $serviceTypes->values();

        for ($dayOffset = $this->days - 1; $dayOffset >= 0; $dayOffset--) {
            $date = $today->subDays($dayOffset);
            $dailyCount = 5 + abs(crc32('servis'.$branch->kode_cabang.$date->toDateString())) % 4;
            for ($slot = 1; $slot <= $dailyCount; $slot++) {
                $seed = ($dayOffset * 29) + ($slot * 13) + ($branch->id * 5);
                $primaryType = $serviceTypes[$seed % $serviceTypes->count()];
                $secondaryType = $serviceTypes[($seed + 2) % $serviceTypes->count()];
                $primaryPrice = (int) $primaryType->harga_default;
                $hasSecondService = $slot === 5 && $dayOffset % 3 === 0;
                $secondaryPrice = $hasSecondService ? (int) $secondaryType->harga_default : 0;
                $timestamp = $date->setTime(9 + ($slot % 8), ($slot * 9) % 60);
                $status = $dayOffset > 7 ? 'sudah_diambil' : ($slot <= 2 ? 'selesai' : 'diproses');
                $note = sprintf('SRV/%s/%s/%04d', $branch->kode_cabang, $date->format('Y'), $serviceNumber++);

                $notes[] = [
                    'nomor_nota' => $note,
                    'branch_id' => $branch->id,
                    'customer_id' => $customerIds[$seed % count($customerIds)],
                    'product_id_ref' => null,
                    'sale_id_ref' => null,
                    'asal_barang' => 'barang_luar',
                    'nama_barang_manual' => $categories[$seed % count($categories)].' pelanggan',
                    'berat' => $this->weightFor($categories[$seed % count($categories)], $seed),
                    'jenis_service_id' => $primaryType->id,
                    'harga_service' => $primaryPrice + $secondaryPrice,
                    'status_service' => $status,
                    'tanggal_masuk' => $date->toDateString(),
                    'estimasi_selesai' => $date->addDays(2)->toDateString(),
                    'tanggal_ambil' => $status === 'sudah_diambil' ? $date->addDays(2)->toDateString() : null,
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'created_by' => $operatorId,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $itemReferences[] = compact('note', 'primaryType', 'primaryPrice', 'secondaryType', 'secondaryPrice', 'hasSecondService', 'timestamp');
            }
        }

        $this->insertChunks('nota_layanan', $notes);
        $noteIds = $this->idsByValues('nota_layanan', 'nomor_nota', array_column($itemReferences, 'note'));
        $items = [];
        foreach ($itemReferences as $reference) {
            $items[] = [
                'service_note_id' => $noteIds[$reference['note']],
                'service_type_id' => $reference['primaryType']->id,
                'harga_service' => $reference['primaryPrice'],
                'keterangan' => 'Pengerjaan utama',
                'created_at' => $reference['timestamp'],
                'updated_at' => $reference['timestamp'],
            ];
            if ($reference['hasSecondService']) {
                $items[] = [
                    'service_note_id' => $noteIds[$reference['note']],
                    'service_type_id' => $reference['secondaryType']->id,
                    'harga_service' => $reference['secondaryPrice'],
                    'keterangan' => 'Pengerjaan tambahan',
                    'created_at' => $reference['timestamp'],
                    'updated_at' => $reference['timestamp'],
                ];
            }
        }
        $this->insertChunks('item_nota_layanan', $items);
    }

    /** @param array<int, string> $categories */
    private function seedInventoryConfigurations(Cabang $branch, array $categories, CarbonImmutable $today): void
    {
        $rows = [];
        foreach ($categories as $category) {
            for ($karat = 8; $karat <= 24; $karat++) {
                $selector = abs((int) crc32($branch->kode_cabang.'|'.$category.'|'.$karat));
                $rows[] = [
                    'branch_id' => $branch->id,
                    'kategori' => $category,
                    'kadar' => $karat.'K',
                    'lead_time_hari' => [3, 7, 14, 21][$selector % 4],
                    'safety_stock' => 1 + ($selector % 6),
                    'created_at' => $today,
                    'updated_at' => $today,
                ];
            }
        }

        $methodRows = [];
        foreach ($rows as $row) {
            foreach (['SMA', 'WMA', 'ARIMA', 'LightGBM'] as $method) {
                $methodRows[] = $row + ['method' => $method];
            }
        }
        $this->insertChunks('konfigurasi_persediaan', $methodRows);
    }

    /**
     * @param  array<int, string>  $categories
     * @return array<int, array{category:string,karat:string,quantity:int}>
     */
    private function stockTargets(Cabang $branch, array $categories): array
    {
        $levels = [0, 2, 4, 6, 9, 12, 16];
        $targets = [];
        foreach ($categories as $category) {
            for ($karat = 8; $karat <= 24; $karat++) {
                $selector = abs((int) crc32('stok|'.$branch->kode_cabang.'|'.$category.'|'.$karat));
                $targets[] = [
                    'category' => $category,
                    'karat' => $karat.'K',
                    'quantity' => $levels[$selector % count($levels)],
                ];
            }
        }

        $total = array_sum(array_column($targets, 'quantity'));
        $cursor = 0;
        while ($total < self::AVAILABLE_PRODUCTS_PER_BRANCH) {
            $index = $cursor++ % count($targets);
            if ($targets[$index]['quantity'] === 0) {
                continue;
            }
            $targets[$index]['quantity']++;
            $total++;
        }
        $cursor = count($targets) - 1;
        while ($total > self::AVAILABLE_PRODUCTS_PER_BRANCH) {
            $index = $cursor--;
            if ($cursor < 0) {
                $cursor = count($targets) - 1;
            }
            if ($targets[$index]['quantity'] <= 1) {
                continue;
            }
            $targets[$index]['quantity']--;
            $total--;
        }

        return $targets;
    }

    /** @param array<int, string> $categories @param array<string, int> $prices @return array{0:string,1:string,2:string,3:float,4:int,5:int} */
    private function productDetail(int $seed, array $categories, array $prices, int $dayOffset, ?string $forcedCategory = null, ?string $forcedKarat = null): array
    {
        $categoryPool = [
            'Anting', 'Anting', 'Anting', 'Anting',
            'Cincin', 'Cincin', 'Cincin', 'Cincin', 'Cincin',
            'Kalung', 'Kalung', 'Kalung',
            'Gelang', 'Gelang', 'Gelang', 'Gelang',
            'Liontin', 'Liontin',
        ];
        $karatPool = [
            '8K', '8K', '9K', '10K', '10K', '11K', '12K', '12K', '13K', '14K', '14K', '15K',
            '16K', '16K', '16K', '17K', '17K', '17K', '18K', '18K', '18K', '19K', '20K', '20K',
            '21K', '22K', '22K', '23K', '24K',
        ];
        $category = $forcedCategory ?? $categoryPool[abs(($seed * 7) + ($dayOffset * 3)) % count($categoryPool)];
        $names = [
            'Anting' => ['Anting Viola', 'Anting Gipsi', 'Anting Cor', 'Anting Bali', 'Anting Bulat'],
            'Cincin' => ['Cincin Cor', 'Cincin Anak', 'Cincin Bunton', 'Cincin Solitaire', 'Cincin Ukir'],
            'Kalung' => ['Kalung Cor', 'Kalung Viola', 'Kalung Rantai', 'Kalung Italia', 'Kalung Snake'],
            'Gelang' => ['Gelang Anak', 'Gelang Rantai', 'Gelang Plat', 'Gelang Bangle', 'Gelang Bola'],
            'Liontin' => ['Liontin Hati', 'Liontin Salib', 'Liontin Berlian', 'Liontin Huruf', 'Liontin Klasik'],
        ];
        $karat = $forcedKarat ?? $karatPool[abs(($seed * 11) + ($dayOffset * 5) + strlen($category)) % count($karatPool)];
        $weight = $this->weightFor($category, $seed);
        $price = $prices[$karat];
        $makingCost = 85000 + (($seed % 7) * 25000);

        return [$category, $names[$category][$seed % count($names[$category])], $karat, $weight, $price, $makingCost];
    }

    private function weightFor(string $category, int $seed): float
    {
        [$minimum, $spread] = match ($category) {
            'Anting' => [0.8, 3.6],
            'Cincin' => [1.2, 5.8],
            'Kalung' => [4.0, 16.0],
            'Gelang' => [3.0, 12.0],
            default => [1.0, 6.0],
        };

        return round($minimum + (($seed % (int) ($spread * 10)) / 10), 3);
    }

    private function productCode(Cabang $branch, string $category, int $number): string
    {
        return sprintf(
            '%s-%s-%03d',
            ProductCode::abbreviation($branch->kode_cabang),
            ProductCode::abbreviation($category),
            $number,
        );
    }

    /** @return array<string, mixed> */
    private function productRow(string $code, int $branchId, string $name, string $category, int $categoryId, string $karat, float $weight, float $price, int $makingCost, float $total, string $photoPath, string $status, CarbonImmutable $timestamp): array
    {
        return [
            'kode_barang' => $code,
            'creation_batch' => null,
            'branch_id' => $branchId,
            'nama_barang' => $name,
            'kategori_barang_id' => $categoryId,
            'kadar' => $karat,
            'berat' => $weight,
            'harga_per_gram' => $price,
            'ongkos' => $makingCost,
            'total_harga' => $total,
            'deskripsi' => 'Perhiasan emas '.$karat.' koleksi '.$category.', model '.$name.'. Berat '.number_format($weight, 3).' gram.',
            'photo_path' => $photoPath,
            'status' => $status,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function sequenceRow(int $branchId, int $categoryId, int $lastNumber): array
    {
        return [
            'branch_id' => $branchId,
            'kategori_barang_id' => $categoryId,
            'last_number' => $lastNumber,
        ];
    }

    /**
     * Bulk inserts sengaja dipakai agar data demo besar tetap cepat dibuat.
     * Karena bulk insert melewati event model, snapshot nota perlu diisi
     * eksplisit supaya nota historis tidak berubah saat master data diedit.
     */
    private function backfillReceiptSnapshots(): void
    {
        foreach (ReceiptSnapshot::MODELS as $class) {
            $table = (new $class)->getTable();
            $relations = ReceiptSnapshot::relations(new $class);

            if (in_array('product', $relations, true)) {
                $relations[] = 'product.kategoriBarang';
            }

            $class::with($relations)
                ->whereNull('receipt_snapshot')
                ->chunkById(500, function ($rows) use ($table): void {
                    $cases = [];
                    $bindings = [];

                    foreach ($rows as $row) {
                        $cases[] = 'WHEN ? THEN ?';
                        $bindings[] = $row->id;
                        $bindings[] = json_encode(ReceiptSnapshot::capture($row), JSON_THROW_ON_ERROR);
                    }

                    $grammar = DB::connection()->getQueryGrammar();
                    $ids = $rows->modelKeys();
                    DB::update(
                        'UPDATE '.$grammar->wrapTable($table)
                        .' SET '.$grammar->wrap('receipt_snapshot').' = CASE '.$grammar->wrap('id').' '.implode(' ', $cases).' END'
                        .' WHERE '.$grammar->wrap('id').' IN ('.implode(',', array_fill(0, count($ids), '?')).')'
                        .' AND '.$grammar->wrap('receipt_snapshot').' IS NULL',
                        array_merge($bindings, $ids),
                    );
                });
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    /** @param array<int, string> $values @return array<string, int> */
    private function idsByValues(string $table, string $column, array $values): array
    {
        $ids = [];
        foreach (array_chunk(array_unique($values), 500) as $chunk) {
            foreach (DB::table($table)->whereIn($column, $chunk)->pluck('id', $column) as $value => $id) {
                $ids[(string) $value] = (int) $id;
            }
        }

        return $ids;
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    /** @return array{0: string, 1: string} */
    private function demandMix(Cabang $branch, CarbonImmutable $date, int $slot, int $dayOffset): array
    {
        // Distribusi probabilistik yang dapat direproduksi: preferensi cabang,
        // tren kategori, musim, akhir pekan, serta permintaan kadar yang berbeda.
        $categoryWeights = ['Anting' => 24, 'Cincin' => 32, 'Kalung' => 15, 'Gelang' => 21, 'Liontin' => 8];
        foreach ($categoryWeights as $category => &$weight) {
            $profile = abs(crc32($branch->kode_cabang.'|'.$category));
            $trend = ($this->days - 1 - $dayOffset) / $this->days;
            $weight *= (0.65 + ($profile % 90) / 100)
                * (1 + (($profile % 2) ? 0.65 : -0.35) * $trend)
                * (1 + 0.25 * sin(2 * M_PI * ($date->dayOfYear + $profile % 365) / 365));
            if ($date->isWeekend() && in_array($category, ['Cincin', 'Gelang'], true)) {
                $weight *= 1.3;
            }
        }
        unset($weight);
        $karatWeights = ['8K' => 4, '9K' => 3, '10K' => 7, '11K' => 3, '12K' => 8, '13K' => 3, '14K' => 10, '15K' => 4, '16K' => 12, '17K' => 17, '18K' => 17, '19K' => 4, '20K' => 7, '21K' => 5, '22K' => 12, '23K' => 3, '24K' => 3];
        foreach ($karatWeights as $karat => &$weight) {
            $weight *= 0.7 + abs(crc32($branch->kode_cabang.'|'.$karat)) % 80 / 100;
            if ($date->month >= 8 && in_array($karat, ['16K', '17K', '18K'], true)) {
                $weight *= 1.3;
            }
        }
        unset($weight);
        $key = $branch->kode_cabang.'|'.$date->toDateString().'|'.$slot;

        return [$this->weightedChoice($categoryWeights, 'category|'.$key), $this->weightedChoice($karatWeights, 'karat|'.$key)];
    }

    /** @param array<string, float|int> $weights */
    private function weightedChoice(array $weights, string $key): string
    {
        // SHA-256 keeps category and karat draws independent. CRC32 would
        // correlate fixed prefixes and suppress many category/karat pairs.
        $draw = (hexdec(substr(hash('sha256', $key), 0, 8)) / 4294967296) * array_sum($weights);
        foreach ($weights as $value => $weight) {
            $draw -= $weight;
            if ($draw < 0) {
                return $value;
            }
        }

        return array_key_last($weights);
    }

    private function seedNoteSequences(): void
    {
        foreach (['penjualan' => 'PJL', 'pembelian' => 'PBL', 'nota_layanan' => 'SRV'] as $table => $prefix) {
            $rows = DB::table($table)
                ->selectRaw('branch_id, SUBSTR(nomor_nota, 9, 4) AS year, MAX(CAST(SUBSTR(nomor_nota, 14) AS UNSIGNED)) AS last_number')
                ->groupBy('branch_id')->groupByRaw('SUBSTR(nomor_nota, 9, 4)')->get();
            $this->insertChunks('urutan_nomor_nota', $rows->map(fn ($row) => [
                'branch_id' => $row->branch_id, 'prefix' => $prefix,
                'year' => (int) $row->year, 'last_number' => (int) $row->last_number,
            ])->all());
        }
    }

    private function pricesFor(CarbonImmutable $date): array
    {
        $base = $this->antam[$date->toDateString()];
        $prices = [];
        for ($k = 8; $k <= 24; $k++) {
            $prices[$k.'K'] = round($base * $k / 24, 2);
        }

        return $prices;
    }

    private function eidIncrease(Cabang $branch, CarbonImmutable $date): int
    {
        if (! filter_var(env('DEMO_EID_UPLIFT', true), FILTER_VALIDATE_BOOL)) {
            return 0;
        }
        foreach (BranchCalendar::EID_DATES as $eid) {
            $diff = (int) $date->diffInDays(CarbonImmutable::parse($eid, 'Asia/Jakarta'), false);
            if ($diff >= 1 && $diff <= 21) {
                return 5 + abs(crc32($branch->kode_cabang.$date->year)) % 16;
            }
        }

        return 0;
    }
}
