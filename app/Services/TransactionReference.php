<?php

namespace App\Services;

use App\Models\{Barang, NotaLayanan, Pembelian, Penjualan};
use Illuminate\Validation\ValidationException;

class TransactionReference
{
    /** Call inside the same database transaction that writes the dependent note. */
    public static function lockSaleItem(int $saleId, int $productId): Penjualan
    {
        $sale = Penjualan::lockForUpdate()->findOrFail($saleId);
        if ($sale->status !== 'valid' || ! $sale->items()->where('product_id', $productId)->exists()) {
            throw ValidationException::withMessages(['nomor_nota' => 'Nota penjualan sudah tidak valid atau barang tidak tercantum pada nota. Ambil ulang data nota.']);
        }
        $product = Barang::lockForUpdate()->find($productId);
        if (! $product || $product->status !== 'terjual') {
            throw ValidationException::withMessages(['nomor_nota' => 'Status barang sudah berubah. Ambil ulang data nota.']);
        }
        if (Pembelian::where('sale_id_ref', $saleId)->where('status', 'valid')->whereHas('items', fn ($q) => $q->where('product_id_ref', $productId))->exists()) {
            throw ValidationException::withMessages(['nomor_nota' => 'Barang pada nota ini sudah dibeli kembali.']);
        }

        return $sale;
    }

    public static function assertNoOpenService(int $saleId, int $productId): void
    {
        if (NotaLayanan::where('sale_id_ref', $saleId)->where('product_id_ref', $productId)->whereNotIn('status_service', ['batal', 'sudah_diambil'])->exists()) {
            throw ValidationException::withMessages(['nomor_nota' => 'Barang masih memiliki nota service yang belum diambil. Selesaikan atau batalkan service terlebih dahulu.']);
        }
    }
}
