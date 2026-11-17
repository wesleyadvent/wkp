<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAudit extends Model
{
    protected $table = 'log_audit';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

public function user(): BelongsTo
{
    $guard = config('auth.defaults.guard');
    $provider = config("auth.guards.{$guard}.provider");
    $userModel = config("auth.providers.{$provider}.model");

    return $this->belongsTo($userModel, 'user_id');
}
}