<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$end=Carbon\CarbonImmutable::parse('2026-11-12','Asia/Jakarta');
$service=app(App\Services\InventoryAnalysisService::class);
foreach(['SMA','WMA','ARIMA'] as $method) {
    $rows=$service->analyze(1,30,$end,$method);
    $example=$rows->first(fn($r)=>$r['kategori']==='Anting' && $r['kadar']==='18K');
    echo json_encode(['method'=>$method,'example'=>$example,'coverage'=>$rows->countBy('metode_efektif')],JSON_UNESCAPED_UNICODE).PHP_EOL;
}
echo json_encode(['batch'=>Illuminate\Support\Facades\DB::table('inventory_forecasts')->selectRaw('status, COUNT(*) as n')->groupBy('status')->get()]).PHP_EOL;
