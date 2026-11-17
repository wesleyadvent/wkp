<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonfigurasiPersediaan extends Model
{
    protected $table = 'konfigurasi_persediaan';

    protected $fillable = [
        'method',
        'branch_id',
        'kategori',
        'kadar',
        'lead_time_hari',
        'safety_stock',
    ];

    protected $casts = [
        'lead_time_hari' => 'integer',
        'safety_stock' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }
}
