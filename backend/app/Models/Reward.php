<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'poin',
        'stok',
        'kategori',
        'gambar',
        'is_active',
    ];

    protected $appends = [
        'gambar_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'poin' => 'integer',
        'stok' => 'integer',
    ];

    public function getGambarUrlAttribute(): ?string
    {
        if (! $this->gambar) {
            return null;
        }

        if (filter_var($this->gambar, FILTER_VALIDATE_URL)) {
            return $this->gambar;
        }

        return url('storage/' . ltrim($this->gambar, '/'));
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}