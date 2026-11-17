<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    protected $table = 'cabang';

    protected $fillable = [
        'kode_cabang',
        'nama_cabang',
        'alamat',
        'telepon',
        'status',
        'is_login_enabled',
        'login_disabled_at',
        'login_disabled_by',
        'login_disabled_reason',
    ];

    protected $casts = [
        'is_login_enabled' => 'boolean',
        'login_disabled_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'branch_id');
    }
}
