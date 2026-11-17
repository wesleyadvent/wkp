<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = ['nama', 'alamat', 'telepon', 'created_by', 'branch_id'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }
}
