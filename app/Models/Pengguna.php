<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Cabang;
use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';

    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'branch_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBranchUser(): bool
    {
        return $this->role === 'toko_cabang';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
