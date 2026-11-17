<?php

namespace App\Models;

use App\Support\ProductCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    protected $table = 'barang';

    use SoftDeletes;

    protected $fillable = [
        'kode_barang',
        'creation_batch',
        'branch_id',
        'kategori_barang_id',
        'nama_barang',
        'kadar',
        'berat',
        'harga_per_gram',
        'ongkos',
        'total_harga',
        'deskripsi',
        'photo_path',
        'status',
    ];

    protected $casts = [
        'berat' => 'decimal:3',
        'harga_per_gram' => 'decimal:2',
        'ongkos' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    /** @var list<string> */
    protected $appends = ['kategori'];

    protected static function booted(): void
    {
        static::creating(function (Barang $product): void {
            if (! filled($product->kode_barang)) {
                $product->kode_barang = ProductCode::make(
                    (int) $product->branch_id,
                    (int) $product->kategori_barang_id,
                );
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }

    public function kategoriBarang(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_barang_id');
    }

    /**
     * Compatibility attribute for templates and API consumers. Its value is
     * always read from the category master, never stored in `barang`.
     */
    public function getKategoriAttribute(): ?string
    {
        return $this->relationLoaded('kategoriBarang')
            ? $this->kategoriBarang?->nama_kategori
            : $this->kategoriBarang()->value('nama_kategori');
    }
}
