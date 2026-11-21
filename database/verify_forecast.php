<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$f = fopen(storage_path('app/private/dataset-forecast.csv'), 'r');
$header = fgetcsv($f);
$example = null; $violations = 0;
while (($row = fgetcsv($f)) !== false) {
    $r = array_combine($header, $row);
    if ($r['split'] === 'latih' && $r['akhir_target'] > '2024-12-31') $violations++;
    if ($r['split'] === 'validasi' && ($r['tanggal_prediksi'] < '2025-01-01' || $r['akhir_target'] > '2025-09-30')) $violations++;
    if ($r['split'] === 'uji' && ($r['tanggal_prediksi'] < '2025-10-01' || $r['akhir_target'] > '2026-07-31')) $violations++;
    if ($r['tanggal_prediksi'] === '2026-03-14' && $r['kode_cabang'] === 'PWG' && $r['kategori'] === 'Anting' && $r['kadar'] === '16K') $example = $r;
}
fclose($f);
$actual = DB::table('item_penjualan as i')->join('penjualan as s','s.id','=','i.sale_id')->join('barang as b','b.id','=','i.product_id')->join('kategori_barang as c','c.id','=','b.kategori_barang_id')->join('cabang as cab','cab.id','=','s.branch_id')->where('cab.kode_cabang','PWG')->where('c.nama_kategori','Anting')->where('b.kadar','16K')->where('s.status','valid')->whereBetween('s.tanggal',['2026-03-15','2026-03-28'])->count();
echo json_encode(['contoh'=>$example,'target_transaksi'=>$actual,'split_pelanggaran'=>$violations]).PHP_EOL;
DB::beginTransaction();
$branch = App\Models\Cabang::where('kode_cabang','MDR')->firstOrFail();
$category = App\Models\KategoriBarang::where('nama_kategori','Anting')->firstOrFail();
$next = App\Support\ProductCode::make($branch->id,$category->id);
echo json_encode(['kode_berikut'=>$next,'sudah_ada'=>DB::table('barang')->where('kode_barang',$next)->exists()]).PHP_EOL;
DB::rollBack();
$analysis = app(App\Services\InventoryAnalysisService::class)->analyze(null, 30);
echo json_encode(['riwayat_dan_stok_nol'=>$analysis->where('stok_tersedia',0)->where('penjualan_periode_berjalan','>',0)->count(), 'stok_positif_dan_rekomendasi'=>$analysis->where('stok_tersedia','>',0)->where('jumlah_perlu_disiapkan','>',0)->count()]).PHP_EOL;
foreach (['2026-02-21','2026-03-14','2026-03-21'] as $date) {
    echo json_encode(['tanggal'=>$date,'kalender'=>DB::table('kalender_cabang as k')->join('cabang as c','c.id','=','k.branch_id')->where('k.tanggal',$date)->pluck('k.buka','c.kode_cabang')]).PHP_EOL;
}
$request = Illuminate\Http\Request::create('/');
$admin = App\Models\Pengguna::where('role','admin')->firstOrFail();
$request->setUserResolver(fn () => $admin);
$product = App\Models\Barang::where('status','tersedia')->firstOrFail();
$response = app(App\Http\Controllers\BarangController::class)->photo($request, $product);
echo json_encode(['foto_http_status'=>$response->getStatusCode(), 'foto_mime'=>$response->headers->get('content-type')]).PHP_EOL;
