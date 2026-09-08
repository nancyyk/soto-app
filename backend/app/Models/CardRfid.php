<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardRfid extends Model
{
    protected $table = 'cards_rfid';

    protected $fillable = [
        'user_id',
        'uid_rfid',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
