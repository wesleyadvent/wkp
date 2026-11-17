<?php

namespace App\Providers;

use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\PotonganPembelian;
use App\Models\ItemPembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use App\Models\ItemNotaLayanan;
use App\Services\MasterDataUsage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/komponen'));
        Paginator::defaultView('pagination.default');

        foreach (MasterDataUsage::MASTERS as $class) {
            $class::deleting(fn ($model) => MasterDataUsage::assertUnused($model));
            $class::updating(function ($model): void {
                $original = $model->fresh();
                if (MasterDataUsage::isUsed($original)) {
                    $model->used_at = $original->used_at ?? now();
                }
            });
        }
        foreach ([Barang::class, ItemPembelian::class, Penjualan::class, Pembelian::class, NotaLayanan::class, PotonganPembelian::class, ItemNotaLayanan::class, \App\Models\KonfigurasiPersediaan::class] as $class) {
            $class::saved(fn ($model) => MasterDataUsage::track($model));
        }
    }
}
