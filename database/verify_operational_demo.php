<?php

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\KategoriBarang;
use App\Models\Pengguna;
use App\Services\DailySalesSeries;
use App\Services\InventoryAnalysisService;
use App\Support\BranchCalendar;
use App\Support\ProductCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$end = CarbonImmutable::now('Asia/Jakarta')->subDay()->startOfDay();
$days = max(365, (int) env('DEMO_HISTORY_DAYS', 365));
$start = $end->subDays($days - 1);
$errors = [];
$require = function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) {
        $errors[] = $message;
    }
};
$report = ['database' => DB::connection()->getDatabaseName(), 'from' => $start->toDateString(), 'to' => $end->toDateString(), 'days' => $days, 'branches' => []];
$require(Pengguna::count() === 5, 'Harus tepat lima akun.');
$require(Pengguna::where('role', 'admin')->count() === 1, 'Harus tepat satu admin.');
foreach (Pengguna::all() as $user) {
    $require(Hash::check('password', $user->password), 'Password akun tidak sesuai: '.$user->email);
}
$require(Cabang::count() === 4, 'Harus tepat empat cabang.');
$require(KategoriBarang::orderBy('nama_kategori')->pluck('nama_kategori')->all() === ['Anting', 'Cincin', 'Gelang', 'Kalung', 'Liontin'], 'Kategori barang tidak sesuai.');
$require(Storage::disk('public')->exists('product-photos/perhiasan-emas.png'), 'Foto produk tidak tersedia.');
$require(hash_file('sha256', base_path('docs/image.png')) === hash_file('sha256', Storage::disk('public')->path('product-photos/perhiasan-emas.png')), 'Foto produk berbeda dari sumber.');

foreach (Cabang::orderBy('id')->get() as $branch) {
    $summary = ['code' => $branch->kode_cabang, 'customers' => DB::table('pelanggan')->where('branch_id', $branch->id)->count(), 'available_stock' => Barang::where('branch_id', $branch->id)->where('status', 'tersedia')->count()];
    $require($summary['customers'] >= 300, 'Pelanggan kurang: '.$branch->kode_cabang);
    $require($summary['available_stock'] === 600, 'Stok bukan 600: '.$branch->kode_cabang);
    $email = strtolower(str_replace('Cabang ', '', $branch->nama_cabang)).'@wijayakusuma.test';
    $require(Pengguna::where('branch_id', $branch->id)->where('email', $email)->where('role', 'toko_cabang')->where('is_active', true)->count() === 1, 'Akun cabang salah: '.$email);
    $calendar = DB::table('kalender_cabang')->where('branch_id', $branch->id)->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->get()->keyBy('tanggal');
    $require($calendar->count() === $days, 'Kalender tidak lengkap: '.$branch->kode_cabang);
    $summary['open_days'] = $calendar->where('buka', true)->count();
    foreach (['penjualan' => ['tanggal', 10, 50], 'pembelian' => ['tanggal', 15, 30], 'nota_layanan' => ['tanggal_masuk', 5, 8]] as $table => [$column, $minimum, $maximum]) {
        $daily = DB::table($table)->where('branch_id', $branch->id)->selectRaw($column.' AS day, COUNT(*) AS n')->groupBy($column)->pluck('n', 'day');
        $summary[$table] = ['total' => $daily->sum(), 'min_per_open_day' => $daily->min(), 'max_per_open_day' => $daily->max(), 'distinct_daily_counts' => $daily->unique()->count()];
        foreach ($daily as $date => $count) {
            $require($date >= $start->toDateString() && $date <= $end->toDateString(), 'Transaksi di luar rentang: '.$table.' '.$date);
        }
        for ($date = $start; $date <= $end; $date = $date->addDay()) {
            $key = $date->toDateString();
            $open = BranchCalendar::isOpen($branch->kode_cabang, $date);
            $count = (int) ($daily[$key] ?? 0);
            $require($calendar->has($key) && (bool) $calendar[$key]->buka === $open && (bool) $calendar[$key]->pencatatan_lengkap, 'Kalender tidak cocok: '.$branch->kode_cabang.' '.$key);
            $require($open ? ($count >= $minimum && $count <= $maximum) : $count === 0, 'Jumlah transaksi tidak sesuai: '.$branch->kode_cabang.' '.$table.' '.$key.' '.$count);
        }
    }
    $stocks = Barang::where('branch_id', $branch->id)->where('status', 'tersedia')->selectRaw('kategori_barang_id, kadar, COUNT(*) AS n')->groupBy('kategori_barang_id', 'kadar')->get();
    $summary['stock_groups'] = $stocks->count();
    $summary['stock_group_min'] = $stocks->min('n');
    $summary['stock_group_max'] = $stocks->max('n');
    $require($stocks->pluck('kadar')->unique()->count() === 17, 'Stok harus mencakup 8K-24K: '.$branch->kode_cabang);
    $require($stocks->pluck('kategori_barang_id')->unique()->count() === 5, 'Kategori stok tidak lengkap: '.$branch->kode_cabang);
    foreach (['SMA', 'WMA', 'ARIMA', 'LightGBM'] as $method) {
        $configs = DB::table('konfigurasi_persediaan')->where('branch_id', $branch->id)->where('method', $method)->get();
        $require($configs->count() === 85 && $configs->pluck('lead_time_hari')->unique()->sort()->values()->all() === [3, 7, 14, 21], 'Konfigurasi tidak lengkap: '.$branch->kode_cabang.' '.$method);
    }
    $report['branches'][] = $summary;
}

$badCodes = DB::table('barang as b')->join('cabang as c', 'c.id', '=', 'b.branch_id')->join('kategori_barang as k', 'k.id', '=', 'b.kategori_barang_id')->whereRaw("b.kode_barang NOT REGEXP CONCAT('^', c.kode_cabang, '-', UPPER(LEFT(k.nama_kategori, 3)), '-[0-9]{3,}$')")->count();
$require($badCodes === 0, 'Kode barang tidak sesuai format aplikasi.');
$require(DB::table('barang')->whereNull('photo_path')->orWhere('photo_path', '!=', 'product-photos/perhiasan-emas.png')->count() === 0, 'Ada barang tanpa foto.');
DB::beginTransaction();
try {
    $branch = Cabang::where('kode_cabang', 'MDR')->firstOrFail();
    $category = KategoriBarang::where('nama_kategori', 'Anting')->firstOrFail();
    $next = ProductCode::make($branch->id, $category->id);
    $require(! Barang::withTrashed()->where('kode_barang', $next)->exists(), 'Kode otomatis berikutnya bentrok.');
    $report['next_code_example'] = $next;
} finally {
    DB::rollBack();
}
$groups = app(DailySalesSeries::class)->groups(null, $end, $days);
$require(count($groups) === 340, 'Harus tersedia 340 seri kategori/kadar/cabang.');
$require(! collect($groups)->contains(fn ($group) => in_array(null, $group['values'], true)), 'Seri harian memiliki data kosong.');
$report['complete_forecast_series'] = count($groups);
foreach (['SMA', 'WMA'] as $method) {
    $rows = app(InventoryAnalysisService::class)->analyze(null, 30, $end, $method);
    $report['analysis'][$method] = ['groups' => $rows->count(), 'statuses' => $rows->countBy('status_persediaan')->all()];
    $require($rows->count() === 340 && ! $rows->contains(fn ($row) => $row['prediksi_unit'] === null), 'Analisis tidak lengkap: '.$method);
}
$request = Illuminate\Http\Request::create('/');
$request->setUserResolver(fn () => Pengguna::where('role', 'admin')->firstOrFail());
$response = app(App\Http\Controllers\BarangController::class)->photo($request, Barang::where('status', 'tersedia')->firstOrFail());
$response->prepare($request);
$report['photo_http_status'] = $response->getStatusCode();
$require($response->getStatusCode() === 200 && $response->headers->get('content-type') === 'image/png', 'Foto tidak dapat diakses melalui aplikasi.');
$report['errors'] = array_values(array_unique($errors));
$output = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents(storage_path('app/private/demo-verification.json'), $output);
echo $output;
exit($errors === [] ? 0 : 1);
