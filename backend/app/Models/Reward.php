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

    protected $casts = [
        'is_active' => 'boolean',
        'poin' => 'integer',
        'stok' => 'integer',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}