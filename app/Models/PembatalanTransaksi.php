<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembatalanTransaksi extends Model
{
    protected $table = 'pembatalan_transaksi';

    protected $fillable = ['transaction_type', 'transaction_id', 'alasan', 'verified_admin_id', 'cancelled_at'];

    protected $casts = ['cancelled_at' => 'datetime'];
}
