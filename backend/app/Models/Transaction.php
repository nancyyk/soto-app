<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public $timestamps = false; // Only has created_at, managed manually

    protected $fillable = [
        'user_id',
        'machine_id',
        'jumlah_botol',
        'poin_diperoleh',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_botol'   => 'integer',
            'poin_diperoleh' => 'integer',
            'created_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
