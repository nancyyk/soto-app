<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'machine_id',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
