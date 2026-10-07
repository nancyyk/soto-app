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
}