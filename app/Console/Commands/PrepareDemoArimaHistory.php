<?php

namespace App\Console\Commands;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\ItemPenjualan;
use App\Models\Penjualan;
use App\Services\DailySalesSeries;
use App\Services\DemoArimaCoveragePlanner;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class PrepareDemoArimaHistory extends Command
{
    protected $signature = 'demo:arima-history {--from=} {--through=} {--apply-demo : Tambahkan transaksi simulasi hanya ke wkp_demo}';

    protected $description = 'Audit atau lengkapi hari penjualan positif dataset demo untuk seluruh tanggal potong yang didukung';

    public function handle(DemoArimaCoveragePlanner $planner): int
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== 'wkp_demo' || ! app()->environment('local', 'testing')) {
            $this->error('Perintah ini hanya untuk database wkp_demo pada lingkungan local/testing.');

            return self::FAILURE;
        }

        $calendarSummary = DB::table('kalender_cabang')->selectRaw('branch_id, MIN(tanggal) AS first_date, MAX(CASE WHEN pencatatan_lengkap = 1 THEN tanggal END) AS last_complete')->groupBy('branch_id')->get();
        if ($calendarSummary->isEmpty() || $calendarSummary->contains(fn ($row) => ! $row->last_complete)) {
            $this->error('Riwayat kalender lengkap belum tersedia.');

            return self::FAILURE;
        }
        $start = CarbonImmutable::parse($calendarSummary->max('first_date'), 'Asia/Jakarta');
        $availableEnd = $calendarSummary->min('last_complete');
        $args = Validator::make([
            'from' => $this->option('from') ?: $start->addDays(89)->toDateString(),
            'through' => $this->option('through') ?: $availableEnd,
        ], [
            'from' => 'required|date_format:Y-m-d|after_or_equal:'.$start->addDays(89)->toDateString(),
            'through' => 'required|date_format:Y-m-d|after_or_equal:from|before:today|before_or_equal:'.$availableEnd,
        ])->validate();
        $end = CarbonImmutable::parse($args['through'], 'Asia/Jakarta');
        $days = (int) $start->diffInDays($end) + 1;
        $firstCutoff = (int) $start->diffInDays(CarbonImmutable::parse($args['from'], 'Asia/Jakarta'));
        $groups = app(DailySalesSeries::class)->groups(null, $end, $days);
        $calendar = DB::table('kalender_cabang')->whereBetween('tanggal', [$start->toDateString(), $args['through']])->get()->groupBy('branch_id')->map(fn ($rows) => $rows->keyBy('tanggal'));
        $plan = [];
        $affected = [];
        $units = 0;
        foreach ($groups as $group) {
            $open = [];
            for ($i = 0; $i < $days; $i++) {
                $open[] = (bool) ($calendar[$group['branch_id']][$start->addDays($i)->toDateString()]->buka ?? false);
            }
            $additions = $planner->plan($group['values'], $open, $firstCutoff);
            if ($additions !== []) {
                $affected[] = $group['key'];
            }
            foreach ($additions as $index => $quantity) {
                $date = $start->addDays($index)->toDateString();
                $key = $group['branch_id'].'|'.$date;
                $plan[$key] ??= ['branch_id' => $group['branch_id'], 'date' => $date, 'items' => []];
                $plan[$key]['items'][] = ['category_id' => $group['category_id'], 'category' => $group['kategori'], 'karat' => $group['kadar'], 'quantity' => $quantity];
                $units += $quantity;
            }
        }
        ksort($plan);
        $report = [
            'database' => $database, 'history_from' => $start->toDateString(), 'cutoff_from' => $args['from'], 'cutoff_through' => $args['through'],
            'cutoff_days' => $days - $firstCutoff, 'groups' => count($groups), 'affected_groups' => count($affected),
            'additional_sale_notes' => count($plan), 'additional_sold_units' => $units,
            'mode' => $this->option('apply-demo') ? 'apply-demo' : 'dry-run',
        ];
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if (! $this->option('apply-demo') || $plan === []) {
            return self::SUCCESS;
        }

        $stamp = now('Asia/Jakarta')->format('Ymd-His');
        $backupPath = storage_path('app/private/backups/'.$database.'-before-arima-coverage-'.$stamp.'.sql.gz');
        File::ensureDirectoryExists(dirname($backupPath));
        $changes = [];
        DB::transaction(function () use ($plan, $backupPath, &$changes): void {
            $this->backup($backupPath);
            $this->info('Backup tersimpan: '.$backupPath);
            foreach ($plan as $entry) {
                $changes[] = $this->addDemoSale($entry);
            }
        });
        $report['backup'] = $backupPath;
        $report['changes'] = $changes;
        $reportPath = storage_path('app/private/arima-demo-coverage-'.$stamp.'.json');
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
        $this->info('Transaksi simulasi tersimpan. Manifest: '.$reportPath);
        $this->info('Riwayat berubah; halaman ARIMA akan menghitung ulang batch untuk tanggal yang dipilih.');

        return self::SUCCESS;
    }

    private function addDemoSale(array $entry): array
    {
        $branch = Cabang::findOrFail($entry['branch_id']);
        $date = CarbonImmutable::parse($entry['date'], 'Asia/Jakarta')->setTime(16, 30);
        $customer = DB::table('pelanggan')->where('branch_id', $branch->id)->orderBy('id')->first();
        $employee = DB::table('karyawan')->where('is_active', true)->where('nama', 'Petugas '.$branch->nama_cabang)->first();
        $operator = DB::table('pengguna')->where('branch_id', $branch->id)->where('role', 'toko_cabang')->value('id');
        if (! $customer || ! $employee || ! $operator) {
            throw new \RuntimeException('Pelanggan, petugas, atau operator demo belum lengkap untuk '.$branch->kode_cabang.'.');
        }

        $products = [];
        foreach ($entry['items'] as $item) {
            $price = DB::table('harga_emas')->where('kadar', $item['karat'])->where('tanggal', '<=', $entry['date'])->orderByDesc('tanggal')->value('harga_per_gram');
            if (! $price) {
                throw new \RuntimeException('Harga historis tidak tersedia untuk '.$item['karat'].' pada '.$entry['date'].'.');
            }
            $template = Barang::where('branch_id', $branch->id)->where('kategori_barang_id', $item['category_id'])->where('kadar', $item['karat'])->orderBy('id')->firstOrFail();
            for ($unit = 0; $unit < $item['quantity']; $unit++) {
                $product = new Barang([
                    'branch_id' => $branch->id, 'kategori_barang_id' => $item['category_id'], 'creation_batch' => 'demo-arima-coverage-v1',
                    'nama_barang' => $template->nama_barang, 'kadar' => $item['karat'], 'berat' => $template->berat,
                    'harga_per_gram' => $price, 'ongkos' => $template->ongkos,
                    'total_harga' => round((float) $template->berat * (float) $price, 2) + (float) $template->ongkos,
                    'deskripsi' => 'SIMULASI DEMO ARIMA: tambahan riwayat penjualan untuk pengujian; bukan transaksi riil.',
                    'photo_path' => $template->photo_path, 'status' => 'terjual',
                ]);
                $product->created_at = $date;
                $product->updated_at = $date;
                $product->save();
                $products[] = $product;
            }
        }
        $sale = new Penjualan([
            'nomor_nota' => $this->noteNumber($branch, (int) $date->format('Y')), 'branch_id' => $branch->id,
            'customer_id' => $customer->id, 'employee_id' => $employee->id, 'employee_name' => $employee->nama,
            'created_by' => $operator, 'tanggal' => $entry['date'], 'status' => 'valid',
            'total_berat' => array_sum(array_map(fn ($p) => (float) $p->berat, $products)),
            'total_ongkos' => array_sum(array_map(fn ($p) => (float) $p->ongkos, $products)),
            'total_harga' => array_sum(array_map(fn ($p) => (float) $p->total_harga, $products)),
        ]);
        $sale->created_at = $date;
        $sale->updated_at = $date;
        $sale->save();
        $itemIds = [];
        foreach ($products as $product) {
            $item = new ItemPenjualan([
                'sale_id' => $sale->id, 'product_id' => $product->id, 'berat' => $product->berat,
                'harga_per_gram' => $product->harga_per_gram, 'ongkos' => $product->ongkos, 'subtotal' => $product->total_harga,
            ]);
            $item->created_at = $date;
            $item->updated_at = $date;
            $item->save();
            $itemIds[] = $item->id;
        }

        return ['branch_id' => $branch->id, 'date' => $entry['date'], 'sale_id' => $sale->id, 'note' => $sale->nomor_nota, 'product_ids' => array_map(fn ($p) => $p->id, $products), 'item_ids' => $itemIds];
    }

    private function noteNumber(Cabang $branch, int $year): string
    {
        $base = 'PJL/'.$branch->kode_cabang.'/'.$year;
        DB::table('urutan_nomor_nota')->insertOrIgnore(['branch_id' => $branch->id, 'prefix' => 'PJL', 'year' => $year, 'last_number' => 0]);
        $sequence = DB::table('urutan_nomor_nota')->where('branch_id', $branch->id)->where('prefix', 'PJL')->where('year', $year)->lockForUpdate()->first();
        $number = (int) $sequence->last_number;
        foreach (Penjualan::where('branch_id', $branch->id)->where('nomor_nota', 'like', $base.'/%')->pluck('nomor_nota') as $note) {
            $number = max($number, (int) substr($note, strlen($base) + 1));
        }
        do {
            $note = sprintf('%s/%04d', $base, ++$number);
        } while (Penjualan::where('nomor_nota', $note)->exists());
        DB::table('urutan_nomor_nota')->where('id', $sequence->id)->update(['last_number' => $number]);

        return $note;
    }

    private function backup(string $path): void
    {
        if (file_exists($path)) {
            throw new \RuntimeException('Berkas backup sudah ada; gunakan nama baru.');
        }
        $file = gzopen($path, 'wb9');
        if ($file === false) {
            throw new \RuntimeException('Backup tidak dapat dibuat.');
        }
        $write = static function (string $sql) use ($file): void {
            if (gzwrite($file, $sql) !== strlen($sql)) {
                throw new \RuntimeException('Penulisan backup gagal.');
            }
        };
        try {
            $write("-- Backup wkp_demo before supplemental ARIMA demo sales\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
            $pdo = DB::connection()->getPdo();
            foreach (DB::select('SHOW FULL TABLES') as $tableRow) {
                $details = array_values((array) $tableRow);
                if ($details[1] !== 'BASE TABLE') {
                    continue;
                }
                $table = '`'.str_replace('`', '``', $details[0]).'`';
                $schema = array_values((array) DB::select('SHOW CREATE TABLE '.$table)[0])[1];
                $write('DROP TABLE IF EXISTS '.$table.";\n".$schema.";\n");
                foreach (DB::table($details[0])->cursor() as $row) {
                    $columns = array_map(fn ($key) => '`'.str_replace('`', '``', $key).'`', array_keys((array) $row));
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values((array) $row));
                    $write('INSERT INTO '.$table.' ('.implode(',', $columns).') VALUES ('.implode(',', $values).");\n");
                }
            }
            $write("SET FOREIGN_KEY_CHECKS=1;\n-- Backup complete\n");
        } finally {
            gzclose($file);
        }
    }
}
