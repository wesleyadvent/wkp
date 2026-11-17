<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPenjualan extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'item_penjualan';

    protected $fillable = ['sale_id', 'product_id', 'berat', 'harga_per_gram', 'ongkos', 'subtotal'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'product_id')->withTrashed();
    }
}
