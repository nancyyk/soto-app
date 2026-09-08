<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogCapacity extends Model
{
    public $timestamps = false;

    protected $table = 'log_capacity';

    protected $fillable = [
        'machine_id',
        'persen_kapasitas',
        'tegangan_baterai',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'persen_kapasitas' => 'integer',
            'tegangan_baterai' => 'decimal:2',
            'created_at'       => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
