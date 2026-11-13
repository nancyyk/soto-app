<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'reward_id',
        'poin',
        'status',
        'kode_voucher',
        'shipping_address',
        'recipient_number',
        'ekspedisi',
        'nomor_resi',
    ];

    protected $casts = [
        'poin' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function getKodeVoucherAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->relationLoaded('reward') && $this->reward && $this->reward->kategori === 'Voucher') {
            $prefix = !empty($this->reward->kode) ? $this->reward->kode : 'VCR';
            return 'SOTO-' . strtoupper($prefix) . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
        }

        return $value;
    }
}
