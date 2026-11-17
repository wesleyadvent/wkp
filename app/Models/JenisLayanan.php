<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisLayanan extends Model
{
    protected $table = 'jenis_layanan';

    protected $fillable = ['nama_service', 'harga_default', 'keterangan', 'is_active'];

    protected $casts = ['harga_default' => 'decimal:2', 'is_active' => 'boolean'];
}
