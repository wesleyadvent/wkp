<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemNotaLayanan extends Model
{
    use \App\Models\Concerns\HasReceiptSnapshot;

    protected $table = 'item_nota_layanan';

    protected $fillable = ['service_note_id', 'service_type_id', 'harga_service', 'keterangan'];

    protected $casts = ['harga_service' => 'decimal:2'];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'service_type_id');
    }

    public function serviceNote(): BelongsTo
    {
        return $this->belongsTo(NotaLayanan::class, 'service_note_id');
    }
}
