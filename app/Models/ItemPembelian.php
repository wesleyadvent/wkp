<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPembelian extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'item_pembelian';

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Barang::class, 'product_id_ref')->withTrashed();
    }

    protected $fillable = [
        'purchase_id',
        'product_id_ref',
        'nama_barang',
        'kategori',
        'kadar',
        'berat',
        'harga_per_gram_beli',
        'potongan_per_gram',
        'subtotal',
    ];
}
