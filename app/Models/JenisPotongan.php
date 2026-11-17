<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPotongan extends Model
{
    protected $table = 'jenis_potongan';

    protected $fillable = ['nama_potongan', 'nominal', 'keterangan', 'is_active'];

    protected $casts = ['nominal' => 'decimal:2', 'is_active' => 'boolean'];
}
