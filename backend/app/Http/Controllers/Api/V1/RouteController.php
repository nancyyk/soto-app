<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RecalculateRouteJob;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Rute TSP
 */
class RouteController extends Controller
{
    /**
     * Rute TSP terbaru.
     *
     * @response 200 {"id":1,"total_distance_km":"16.20","total_duration_min":32,"status":"pending","stops":[...]}
     */
    public function latest(): JsonResponse
    {
        $route = Route::with('stops.machine')->latest()->first();

        if (!$route) {
            return response()->json(['message' => 'Belum ada rute tersedia'], 404);
        }

        return response()->json($route);
    }

    /**
     * Trigger ulang kalkulasi TSP secara manual.
     *
     * @response 202 {"message": "TSP recalculation queued"}
     */
    public function recalculate(Request $request): JsonResponse
    {
        RecalculateRouteJob::dispatch();
        return response()->json(['message' => 'TSP recalculation queued'], 202);
    }
}
