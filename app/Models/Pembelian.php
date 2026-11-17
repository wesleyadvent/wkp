<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'pembelian';

    protected $fillable = [
        'nomor_nota',
        'employee_id',
        'employee_name',
        'branch_id',
        'customer_id',
        'sumber_barang',
        'sale_id_ref',
        'tanggal',
        'total_berat',
        'total_potongan',
        'total_harga',
        'status',
        'created_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = ['tanggal' => 'date', 'cancelled_at' => 'datetime'];

    public function cancellation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PembatalanTransaksi::class, 'transaction_id')->where('transaction_type', 'purchase');
    }

    public function referenceSale(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'sale_id_ref');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'cancelled_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPembelian::class, 'purchase_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PotonganPembelian::class, 'purchase_id');
    }
}
