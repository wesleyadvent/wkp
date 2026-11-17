<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
foreach (DB::table('cabang')->get() as $b) {
    $minDate = now('Asia/Jakarta')->startOfDay()->subDays(365)->toDateString();
    $stats = ['cabang'=>$b->kode_cabang,'pelanggan'=>DB::table('pelanggan')->where('branch_id',$b->id)->count(),'stok'=>DB::table('barang')->where('branch_id',$b->id)->where('status','tersedia')->count()];
    foreach (['penjualan'=>'tanggal','pembelian'=>'tanggal','nota_layanan'=>'tanggal_masuk'] as $table=>$field) {
        $base = DB::table($table)->where('branch_id',$b->id);
        $stats[$table] = $base->count();
        $stats[$table.'_awal'] = (clone $base)->min($field);
        $stats[$table.'_akhir'] = (clone $base)->max($field);
        $valid = $table === 'nota_layanan' ? '' : " AND t.status = 'valid'";
        $stats[$table.'_hari_tutup'] = DB::selectOne("SELECT COUNT(*) AS n FROM $table t JOIN kalender_cabang k ON k.branch_id=t.branch_id AND k.tanggal=t.$field WHERE t.branch_id=? AND k.buka=0",[$b->id])->n;
        $minimum = $table === 'penjualan' ? 50 : ($table === 'pembelian' ? 30 : 5);
        $stats[$table.'_kurang_minimum'] = DB::selectOne("SELECT COUNT(*) AS n FROM kalender_cabang k LEFT JOIN (SELECT t.branch_id, t.$field AS hari, COUNT(*) AS n FROM $table t WHERE t.branch_id=? $valid GROUP BY t.branch_id,t.$field) x ON x.branch_id=k.branch_id AND x.hari=k.tanggal WHERE k.branch_id=? AND k.buka=1 AND k.tanggal BETWEEN ? AND ? AND COALESCE(x.n,0) < ?",[$b->id,$b->id,$minDate,now('Asia/Jakarta')->subDay()->toDateString(),$minimum])->n;
    }
    echo json_encode($stats).PHP_EOL;
}
foreach (DB::table('pengguna')->get() as $u) echo json_encode(['email'=>$u->email,'password_ok'=>Hash::check('password',$u->password)]).PHP_EOL;
echo json_encode(['harga_pertama'=>DB::table('harga_antam_harian')->min('tanggal'),'harga_terakhir'=>DB::table('harga_antam_harian')->orderByDesc('tanggal')->first(), 'tanpa_foto'=>DB::table('barang')->whereNull('photo_path')->count(), 'foto_ada'=>file_exists(storage_path('app/public/product-photos/demo-emas.png')), 'kode_demo'=>DB::table('barang')->where('kode_barang','like','DEMO-%')->count(), 'analysis'=>app(App\Services\InventoryAnalysisService::class)->analyze(null,30)->countBy('status_persediaan')]).PHP_EOL;
