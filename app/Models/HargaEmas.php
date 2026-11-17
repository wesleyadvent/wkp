<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaEmas extends Model
{
    protected $table = 'harga_emas';

    protected $fillable = ['tanggal', 'kadar', 'harga_per_gram', 'keterangan', 'created_by'];

    protected $casts = ['tanggal' => 'date', 'harga_per_gram' => 'decimal:2'];
}
