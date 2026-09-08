<?php

namespace App\Jobs;

use App\Events\RouteRecalculated;
use App\Models\Setting;
use App\Services\TspSolverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateRouteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(TspSolverService $solver): void
    {
        $depot = [
            'lat'  => (float) Setting::get('depot_lat', config('soto.depot_lat', -7.2750)),
            'lng'  => (float) Setting::get('depot_lng', config('soto.depot_lng', 112.7900)),
            'nama' => Setting::get('depot_nama', 'Pos Pengangkutan'),
        ];

        $route = $solver->solveAndPersist($depot);

        if ($route) {
            event(new RouteRecalculated($route));
            Log::info('TSP route recalculated', [
                'route_id'   => $route->id,
                'distance'   => $route->total_distance_km,
                'stops'      => $route->stops->count(),
            ]);
        } else {
            Log::info('TSP: no eligible machines above threshold, route skipped.');
        }
    }
}
