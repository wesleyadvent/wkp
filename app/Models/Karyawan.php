<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawan';

    protected $fillable = ['nama', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
