<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = [
        'nama_lokasi',
        'latitude',
        'longitude',
        'is_simulation',
        'status_online',
        'kapasitas_terkini',
        'tegangan_baterai',
        'threshold_capacity',
    ];

    protected function casts(): array
    {
        return [
            'latitude'           => 'float',
            'longitude'          => 'float',
            'is_simulation'      => 'boolean',
            'status_online'      => 'boolean',
            'kapasitas_terkini'  => 'integer',
            'tegangan_baterai'   => 'decimal:2',
            'threshold_capacity' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function logCapacity(): HasMany
    {
        return $this->hasMany(LogCapacity::class);
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function isAboveThreshold(): bool
    {
        return $this->kapasitas_terkini >= $this->threshold_capacity;
    }

    /**
     * Get machines that are at or above their capacity threshold.
     */
    public function scopeAboveThreshold($query)
    {
        return $query->whereColumn('kapasitas_terkini', '>=', 'threshold_capacity');
    }
}
