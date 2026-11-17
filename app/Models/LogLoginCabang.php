<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogLoginCabang extends Model
{
    protected $table = 'log_login_cabang';

    public $timestamps = false;

    protected $fillable = ['branch_id', 'admin_id', 'old_status', 'new_status', 'alasan', 'ip_address', 'user_agent', 'created_at'];

    protected $casts = ['old_status' => 'boolean', 'new_status' => 'boolean', 'created_at' => 'datetime'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }
}
