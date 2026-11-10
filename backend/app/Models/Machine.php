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
        'last_seen_at',
        'kapasitas_terkini',
        'tegangan_baterai',
        'threshold_capacity',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_simulation' => 'boolean',
            'status_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'kapasitas_terkini' => 'integer',
            'tegangan_baterai' => 'decimal:2',
            'threshold_capacity' => 'integer',
        ];
    }

    /** Detik tanpa telemetri sebelum mesin dianggap mati (ESP32 kirim tiap ~5 detik). */
    public const HEARTBEAT_TIMEOUT_SECONDS = 30;

    /** Status online sebenarnya: flag tersimpan DAN masih mengirim heartbeat. */
    public function isOnline(): bool
    {
        if (! $this->status_online) {
            return false;
        }
        if ($this->is_simulation) {
            return true; // node simulasi tidak punya heartbeat
        }

        return $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subSeconds(self::HEARTBEAT_TIMEOUT_SECONDS));
    }

    /** Mesin fisik yang berstatus online tetapi heartbeat-nya sudah basi. */
    public function scopeStale($query)
    {
        return $query->where('status_online', true)
            ->where('is_simulation', false)
            ->where(function ($q) {
                $q->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subSeconds(self::HEARTBEAT_TIMEOUT_SECONDS));
            });
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
