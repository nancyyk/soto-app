<?php

namespace App\Events;

use App\Models\Route;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RouteRecalculated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Route $route) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'RouteRecalculated';
    }

    public function broadcastWith(): array
    {
        return [
            'route_id'          => $this->route->id,
            'total_distance_km' => $this->route->total_distance_km,
            'total_duration_min'=> $this->route->total_duration_min,
            'stops'             => $this->route->stops->map(fn($s) => [
                'machine_id'  => $s->machine_id,
                'nama_lokasi' => $s->machine?->nama_lokasi,
                'urutan'      => $s->urutan,
            ])->values()->toArray(),
        ];
    }
}
