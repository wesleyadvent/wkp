<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaLayanan extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'nota_layanan';

    protected $fillable = [
        'nomor_nota',
        'employee_id',
        'employee_name',
        'branch_id',
        'customer_id',
        'product_id_ref',
        'sale_id_ref',
        'asal_barang',
        'nama_barang_manual',
        'berat',
        'jenis_service_id',
        'harga_service',
        'status_service',
        'tanggal_masuk',
        'estimasi_selesai',
        'tanggal_ambil',
        'created_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'estimasi_selesai' => 'date',
        'tanggal_ambil' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'jenis_service_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'product_id_ref')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemNotaLayanan::class, 'service_note_id');
    }
}
