<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotonganPembelian extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'potongan_pembelian';

    protected $fillable = ['purchase_id', 'deduction_type_id', 'nominal', 'keterangan'];

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(JenisPotongan::class, 'deduction_type_id');
    }
}
