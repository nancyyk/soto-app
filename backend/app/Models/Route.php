<?php

namespace App\Models;

use App\Enums\RouteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'total_distance_km',
        'total_duration_min',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'total_distance_km' => 'decimal:2',
            'total_duration_min' => 'integer',
            'status' => RouteStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('urutan');
    }
}
