<?php

use App\Http\Controllers\AnalisisPersediaanController;
use App\Http\Controllers\ArimaForecastController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DasborController;
use App\Http\Controllers\HargaEmasController;
use App\Http\Controllers\JenisLayananController;
use App\Http\Controllers\JenisPotonganController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAuditController;
use App\Http\Controllers\NotaLayananController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PembatalanTransaksiController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PenghapusanTransaksiController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PerbandinganPrediksiController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', DasborController::class)->middleware(['auth', 'verified', 'branch.login.enabled'])->name('dashboard');

Route::middleware(['auth', 'branch.login.enabled'])->group(function () {
    Route::get('/profile', [ProfilController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfilController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfilController::class, 'destroy'])->name('profile.destroy');

    Route::get('products/labels', [BarangController::class, 'labels'])->name('products.labels');
    Route::get('products/{product}/barcode', [BarangController::class, 'barcode'])->name('products.barcode');
    Route::resource('products', BarangController::class)->only(['index']);
    Route::get('products/{product}/photo', [BarangController::class, 'photo'])->name('products.photo');
    Route::delete('products/{product}/secure-delete', [BarangController::class, 'secureDelete'])->name('products.secure-delete');

    Route::get('sale-customer-query', [PenjualanController::class, 'customerLookup'])->name('sales.customer-query');
    Route::get('sale-barcode-query', [PenjualanController::class, 'barcodeLookup'])->name('sales.barcode-query');
    Route::resource('sales', PenjualanController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('sales/{sale}/print', [PenjualanController::class, 'print'])->name('sales.print');
    Route::get('sales/search-product/{kode}', [PenjualanController::class, 'searchProduct'])->name('sales.search-product');

    Route::resource('purchases', PembelianController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('purchases/{purchase}/print', [PembelianController::class, 'print'])->name('purchases.print');
    Route::get('purchases/find-sale/{nomorNota}', [PembelianController::class, 'findSale'])->name('purchases.find-sale');
    Route::get('purchase-sale-query', [PembelianController::class, 'findSaleQuery'])->name('purchases.find-sale-query');

    Route::resource('service-notes', NotaLayananController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('service-notes/{serviceNote}/print', [NotaLayananController::class, 'print'])->name('service-notes.print');
    Route::patch('service-notes/{serviceNote}/picked-up', [NotaLayananController::class, 'markPickedUp'])->name('service-notes.picked-up');
    Route::patch('service-notes/{serviceNote}/completed', [NotaLayananController::class, 'markCompleted'])->name('service-notes.completed');
    Route::get('service-note-sale-query', [NotaLayananController::class, 'findSaleQuery'])->name('service-notes.find-sale-query');

    Route::resource('customers', PelangganController::class)->except(['show', 'destroy']);
    Route::resource('gold-prices', HargaEmasController::class)->only(['index']);
    Route::get('gold-prices/{gold_price}', [HargaEmasController::class, 'show'])->whereNumber('gold_price')->name('gold-prices.show');
    Route::resource('product-categories', KategoriBarangController::class)->only(['index']);
    Route::resource('deduction-types', JenisPotonganController::class)->only(['index']);
    Route::resource('service-types', JenisLayananController::class)->only(['index']);

    Route::get('reports/sales', [LaporanController::class, 'sales'])->name('reports.sales');
    Route::get('reports/purchases', [LaporanController::class, 'purchases'])->name('reports.purchases');
    Route::get('reports/services', [LaporanController::class, 'services'])->name('reports.services');
    Route::get('reports/profit-loss', [LaporanController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('inventory-analysis', [AnalisisPersediaanController::class, 'index'])->name('inventory-analysis.index');
    Route::post('inventory-analysis/arima/prepare', ArimaForecastController::class)->name('inventory-analysis.arima.prepare');
    Route::get('forecast-comparison', PerbandinganPrediksiController::class)->name('forecast-comparison.index');
    foreach (['sma' => 'SMA', 'wma' => 'WMA', 'arima' => 'ARIMA', 'lightgbm' => 'LightGBM'] as $slug => $method) {
        Route::get('inventory-analysis/'.$slug, [AnalisisPersediaanController::class, 'index'])->defaults('forecast_method', $method)->name('inventory-analysis.'.$slug);
    }

    Route::post('employees/verify', [KaryawanController::class, 'verify'])->middleware('throttle:60,1')->name('employees.verify');
    Route::get('employees', [KaryawanController::class, 'index'])->name('employees.index');

    Route::middleware('role:admin')->group(function () {
        Route::resource('products', BarangController::class)->only(['create', 'store', 'edit', 'update']);
        Route::resource('service-types', JenisLayananController::class)->except(['index', 'show']);
        Route::resource('deduction-types', JenisPotonganController::class)->except(['index', 'show']);
        Route::resource('product-categories', KategoriBarangController::class)->except(['index', 'show']);
        Route::resource('gold-prices', HargaEmasController::class)->except(['index', 'show']);
        Route::resource('employees', KaryawanController::class)->except(['index', 'show']);
        Route::resource('branches', CabangController::class)->except(['show', 'destroy']);
        Route::patch('branches/{branch}/toggle-login', [CabangController::class, 'toggleLogin'])->name('branches.toggle-login');
        Route::get('branch-login-logs', [CabangController::class, 'logs'])->name('branches.login-logs');
        Route::resource('users', PenggunaController::class)->except(['show', 'destroy']);
        Route::get('audit-logs', [LogAuditController::class, 'index'])->name('audit-logs.index');
        Route::get('inventory-settings', [AnalisisPersediaanController::class, 'configurations'])->name('inventory-settings.index');
        Route::get('inventory-settings/edit', [AnalisisPersediaanController::class, 'editConfiguration'])->name('inventory-settings.edit');
        Route::post('inventory-settings', [AnalisisPersediaanController::class, 'storeConfiguration'])->name('inventory-settings.store');
        foreach (['sma' => 'SMA', 'wma' => 'WMA', 'arima' => 'ARIMA', 'lightgbm' => 'LightGBM'] as $slug => $method) {
            Route::get('inventory-settings/'.$slug, [AnalisisPersediaanController::class, 'configurations'])->defaults('forecast_method', $method)->name('inventory-settings.'.$slug.'.index');
            Route::get('inventory-settings/'.$slug.'/edit', [AnalisisPersediaanController::class, 'editConfiguration'])->defaults('forecast_method', $method)->name('inventory-settings.'.$slug.'.edit');
            Route::post('inventory-settings/'.$slug, [AnalisisPersediaanController::class, 'storeConfiguration'])->defaults('forecast_method', $method)->name('inventory-settings.'.$slug.'.store');
        }
        Route::get('transactions/destroy-sales', [PenghapusanTransaksiController::class, 'sales'])->name('transactions.destroy-sales');
        Route::get('transactions/destroy-purchases', [PenghapusanTransaksiController::class, 'purchases'])->name('transactions.destroy-purchases');
        Route::get('transactions/destroy-services', [PenghapusanTransaksiController::class, 'services'])->name('transactions.destroy-services');
        Route::post('transactions/cancel', [PembatalanTransaksiController::class, 'store'])->name('transactions.cancel');
    });
});

require __DIR__.'/auth.php';
