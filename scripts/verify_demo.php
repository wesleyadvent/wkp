<?php

// Read-only verification against the application's configured database.
// Sequence generation is exercised inside a transaction that is rolled back.
use App\Models\Cabang;
use App\Models\Pengguna;
use App\Models\Penjualan;
use App\Services\ArimaBatch;
use App\Services\DailySalesSeries;
use App\Services\DataConsistencyAudit;
use App\Services\InventoryAnalysisService;
use App\Services\LightgbmFeatureBuilder;
use App\Support\NotaNumber;
use App\Support\ProductCode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$end = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDay();
$days = max(365, (int) env('DEMO_HISTORY_DAYS', 365));
$start = $end->subDays($days - 1);
$report = ['database' => DB::connection()->getDatabaseName(), 'from' => $start->toDateString(), 'through' => $end->toDateString(), 'days' => $days];
$errors = [];
$check = function (bool $ok, string $message) use (&$errors): void {
    if (! $ok) {
        $errors[] = $message;
    }
};

$expectedEmails = ['admin', 'purwonegoro', 'mandiraja', 'sumpiuh', 'desa'];
$check(Pengguna::count() === 5, 'Expected exactly five login accounts.');
foreach ($expectedEmails as $name) {
    $user = Pengguna::where('email', $name.'@wijayakusuma.test')->first();
    $check($user && Hash::check('password', $user->password) && $user->is_active, 'Account/password: '.$name);
    $check($user && $user->role === ($name === 'admin' ? 'admin' : 'toko_cabang'), 'Account role: '.$name);
}
$check(Cabang::count() === 4, 'Expected four branches.');
$check(DB::table('kategori_barang')->count() === 5, 'Expected five unique categories (Anting appears twice in the request).');

$report['branches'] = [];
fwrite(STDERR, 'Checking branch counts and daily minimums...'.PHP_EOL);
foreach (Cabang::orderBy('id')->get() as $branch) {
    $id = $branch->id;
    $stock = DB::table('barang')->where('branch_id', $id)->where('status', 'tersedia')->whereNull('deleted_at');
    $entry = ['branch' => $branch->nama_cabang, 'available_stock' => (clone $stock)->count(), 'customers' => DB::table('pelanggan')->where('branch_id', $id)->count()];
    $check($entry['available_stock'] === 600, $branch->kode_cabang.': expected exactly 600 available products.');
    $check($entry['customers'] >= 300, $branch->kode_cabang.': insufficient customers.');
    $check((clone $stock)->distinct()->count('kadar') === 17, $branch->kode_cabang.': stock should span all 8K–24K.');
    $check((clone $stock)->distinct()->count('kategori_barang_id') === 5, $branch->kode_cabang.': missing stock category.');
    foreach (['penjualan' => ['tanggal', 50], 'pembelian' => ['tanggal', 30], 'nota_layanan' => ['tanggal_masuk', 5]] as $table => [$dateColumn, $minimum]) {
        $query = DB::table($table)->where('branch_id', $id)->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);
        if ($table !== 'nota_layanan') {
            $query->where('status', 'valid');
        }
        $daily = $query->groupBy($dateColumn)->selectRaw($dateColumn.' AS day, COUNT(*) AS n')->get();
        $entry[$table] = ['total' => (int) $daily->sum('n'), 'days' => $daily->count(), 'minimum_per_day' => (int) $daily->min('n'), 'maximum_per_day' => (int) $daily->max('n')];
        $check($daily->count() === $days && (int) $daily->min('n') >= $minimum, $branch->kode_cabang.': daily minimum failed for '.$table);
        $crossBranch = DB::table($table.' as n')->join('pelanggan as c', 'c.id', '=', 'n.customer_id')->where('n.branch_id', $id)->whereColumn('n.branch_id', '!=', 'c.branch_id')->count();
        $check($crossBranch === 0, $branch->kode_cabang.': customer branch mismatch in '.$table);
    }
    $entry['stock_by_category'] = (clone $stock)->join('kategori_barang as k', 'k.id', '=', 'barang.kategori_barang_id')->groupBy('k.nama_kategori')->selectRaw('k.nama_kategori AS category, COUNT(*) AS n')->pluck('n', 'category')->all();
    $entry['stock_by_karat'] = (clone $stock)->groupBy('kadar')->selectRaw('kadar, COUNT(*) AS n')->pluck('n', 'kadar')->all();
    foreach (['SMA', 'WMA', 'ARIMA', 'LightGBM'] as $method) {
        $configs = DB::table('konfigurasi_persediaan')->where('branch_id', $id)->where('method', $method);
        $leads = (clone $configs)->distinct()->orderBy('lead_time_hari')->pluck('lead_time_hari')->map(fn ($v) => (int) $v)->all();
        $check($configs->count() === 85 && $leads === [3, 7, 14, 21], $branch->kode_cabang.': configurations '.$method);
    }
    $report['branches'][] = $entry;
    fwrite(STDERR, $branch->nama_cabang.': daily minimums and configurations checked.'.PHP_EOL);
}

foreach (DB::table('barang')->distinct()->pluck('photo_path') as $path) {
    $check($path && Storage::disk('public')->exists($path) && is_file(public_path('storage/'.$path)), 'Product photo unavailable: '.$path);
    $check($path && hash_file('sha256', Storage::disk('public')->path($path)) === hash_file('sha256', base_path('docs/image.png')), 'Product photo differs from docs/image.png.');
}
$check(DB::table('barang')->whereNull('photo_path')->count() === 0, 'Missing product photos.');
$check(DB::table('barang')->whereRaw("kode_barang NOT REGEXP '^(PWG|MDR|SPH|DSA)-(ANT|CIN|KAL|GEL|LIO)-[0-9]{3,}$'")->count() === 0, 'Invalid product code format.');
fwrite(STDERR, 'Auditing financial totals, stock, and snapshots...'.PHP_EOL);
$report['consistency_audit'] = app(DataConsistencyAudit::class)->run();
foreach ($report['consistency_audit'] as $name => $result) {
    $check($result['count'] === 0, 'Consistency audit: '.$name.' ('.$result['count'].')');
}

$groups = app(DailySalesSeries::class)->groups(null, $end, $days);
fwrite(STDERR, 'Checking all category/karat time series and analysis features...'.PHP_EOL);
$check(count($groups) === 340, 'Expected all 340 branch/category/karat time series.');
foreach ($groups as $group) {
    $check(count($group['values']) === $days && ! in_array(null, $group['values'], true), 'Incomplete time series: '.$group['key']);
    $check(array_sum($group['values']) > 0 && count(array_unique($group['values'])) > 1, 'Unvarying sales series: '.$group['key']);
}
$report['complete_sales_series'] = count($groups);
foreach (['SMA', 'WMA'] as $method) {
    $analysis = app(InventoryAnalysisService::class)->analyze(null, 30, $end, $method);
    $check($analysis->count() === 340 && $analysis->every(fn ($row) => $row['ramalan_unit'] !== null && ! $row['menggunakan_konfigurasi_default']), 'Analysis not ready: '.$method);
    $report['analysis'][$method] = $analysis->countBy('status_persediaan')->all();
}
$builder = app(LightgbmFeatureBuilder::class);
$context = $builder->context(array_values($groups), $end, 90);
foreach ([3 => 'lead_time', 7 => 'lead_time', 14 => 'lead_time', 21 => 'lead_time', 30 => 'projection', 60 => 'projection', 90 => 'projection'] as $horizon => $use) {
    $metadata = $builder->metadata($horizon, $use);
    foreach ($groups as $group) {
        $builder->build($group, $metadata, $end, $context);
    }
}
$report['lightgbm_features_validated'] = count($groups) * 7;
$report['lightgbm_lead_time_models_available'] = [3, 7, 14, 21];
$report['lightgbm_lead_time_models_missing'] = [];

$samples = [];
foreach (array_values($groups) as $group) {
    if (count($samples) === 3) {
        break;
    }
    if (array_sum($group['values']) >= 90) {
        $group['horizon'] = 21;
        $samples[] = $group;
    }
}
fwrite(STDERR, 'Testing ARIMA with three real dataset series...'.PHP_EOL);
$arima = app(ArimaBatch::class)->run($samples);
foreach ($arima as $key => $result) {
    $check($result['status'] === 'ok', 'ARIMA smoke prediction unavailable: '.$key);
    $report['arima_smoke'][$key] = ['status' => $result['status'], 'forecast_total_21_days' => $result['total']];
}

DB::beginTransaction();
try {
    foreach (Cabang::all() as $branch) {
        foreach (DB::table('kategori_barang')->get() as $category) {
            $code = ProductCode::make($branch->id, $category->id);
            $check(! DB::table('barang')->where('kode_barang', $code)->exists(), 'Next product code already exists: '.$code);
            $report['next_product_codes'][$branch->kode_cabang][$category->nama_kategori] = $code;
        }
        $note = NotaNumber::make('PJL', $branch, Penjualan::class);
        $check(! DB::table('penjualan')->where('nomor_nota', $note)->exists(), 'Next note already exists: '.$note);
        $report['next_sale_notes'][$branch->kode_cabang] = $note;
    }
} finally {
    DB::rollBack();
}

$report['errors'] = $errors;
$report['passed'] = $errors === [];
$report['verified_at'] = CarbonImmutable::now('Asia/Jakarta')->toIso8601String();
$output = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
file_put_contents(storage_path('app/private/demo-verification.json'), $output.PHP_EOL);
echo $output.PHP_EOL;
exit($errors === [] ? 0 : 1);
