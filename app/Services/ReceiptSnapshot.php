<?php

namespace App\Services;

use App\Models\{ItemNotaLayanan, ItemPembelian, ItemPenjualan, NotaLayanan, Pembelian, Penjualan, PotonganPembelian};
use Illuminate\Database\Eloquent\Model;

class ReceiptSnapshot
{
    public const MODELS = [Penjualan::class, Pembelian::class, NotaLayanan::class, ItemPenjualan::class, ItemPembelian::class, PotonganPembelian::class, ItemNotaLayanan::class];

    public static function relations(Model $model): array
    {
        return match ($model::class) {
            Penjualan::class, Pembelian::class => ['branch', 'customer', 'creator'],
            NotaLayanan::class => ['branch', 'customer', 'creator', 'product', 'serviceType'],
            ItemPenjualan::class, ItemPembelian::class => ['product'],
            PotonganPembelian::class => ['deductionType'],
            ItemNotaLayanan::class => ['serviceType'],
            default => [],
        };
    }

    public static function capture(Model $model): array
    {
        $fields = [
            'branch' => ['id', 'nama_cabang', 'kode_cabang', 'alamat', 'telepon'],
            'customer' => ['id', 'nama', 'alamat', 'telepon'],
            'creator' => ['id', 'name'],
            'product' => ['id', 'kode_barang', 'nama_barang', 'kategori', 'kadar', 'deskripsi', 'photo_path'],
            'deductionType' => ['id', 'nama_potongan'],
            'serviceType' => ['id', 'nama_service'],
        ];
        $snapshot = [];
        foreach (self::relations($model) as $relation) {
            if ($relation === 'product' && $model instanceof NotaLayanan && $model->sale_id_ref) {
                $source = ItemPenjualan::where('sale_id', $model->sale_id_ref)->where('product_id', $model->product_id_ref)->first();
                $snapshot[$relation] = $source?->receipt('product') ? (array) $source->receipt('product') : null;
                continue;
            }
            $related = $model->$relation;
            $snapshot[$relation] = $related?->only($fields[$relation]);
        }

        return $snapshot;
    }
}
