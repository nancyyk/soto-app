<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Setting;

readonly class TspResult
{
    public function __construct(
        public array $orderedNodeIndices,
        public float $totalDistanceKm,
        public int $totalDurationMin,
    ) {}
}

interface TspSolverStrategy
{
    public function solve(array $distanceMatrix, int $depotIndex): TspResult;
}

class BruteForceTspStrategy implements TspSolverStrategy
{
    public function solve(array $distanceMatrix, int $depotIndex): TspResult
    {
        $n = count($distanceMatrix);
        $nodes = range(0, $n - 1);
        $visitables = array_values(array_filter($nodes, fn ($i) => $i !== $depotIndex));

        $bestDistance = PHP_FLOAT_MAX;
        $bestOrder = [];

        $this->permutations($visitables, function (array $perm) use ($depotIndex, $distanceMatrix, &$bestDistance, &$bestOrder) {
            $route = array_merge([$depotIndex], $perm, [$depotIndex]);
            $distance = 0.0;

            for ($i = 0; $i < count($route) - 1; $i++) {
                $distance += $distanceMatrix[$route[$i]][$route[$i + 1]];
            }

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestOrder = $route;
            }
        });

        return new TspResult(
            orderedNodeIndices: $bestOrder,
            totalDistanceKm: round($bestDistance, 2),
            totalDurationMin: (int) ceil(($bestDistance / 30) * 60),
        );
    }

    private function permutations(array $items, callable $callback, array $current = []): void
    {
        if (empty($items)) {
            $callback($current);

            return;
        }
        foreach ($items as $key => $item) {
            $remaining = $items;
            array_splice($remaining, $key, 1);
            $this->permutations($remaining, $callback, array_merge($current, [$item]));
        }
    }
}

class NearestNeighborTwoOptStrategy implements TspSolverStrategy
{
    public function solve(array $distanceMatrix, int $depotIndex): TspResult
    {
        $n = count($distanceMatrix);
        $route = [$depotIndex];
        $unvisited = array_values(array_filter(range(0, $n - 1), fn ($i) => $i !== $depotIndex));

        $current = $depotIndex;
        while (! empty($unvisited)) {
            $nearest = null;
            $nearestDist = PHP_FLOAT_MAX;
            foreach ($unvisited as $idx => $node) {
                if ($distanceMatrix[$current][$node] < $nearestDist) {
                    $nearestDist = $distanceMatrix[$current][$node];
                    $nearest = $idx;
                }
            }
            $route[] = $unvisited[$nearest];
            $current = $unvisited[$nearest];
            array_splice($unvisited, $nearest, 1);
        }
        $route[] = $depotIndex;

        $improved = true;
        while ($improved) {
            $improved = false;
            for ($i = 1; $i < count($route) - 2; $i++) {
                for ($j = $i + 1; $j < count($route) - 1; $j++) {
                    $delta = $distanceMatrix[$route[$i - 1]][$route[$j]]
                           + $distanceMatrix[$route[$i]][$route[$j + 1]]
                           - $distanceMatrix[$route[$i - 1]][$route[$i]]
                           - $distanceMatrix[$route[$j]][$route[$j + 1]];
                    if ($delta < -0.0001) {
                        $route = array_merge(
                            array_slice($route, 0, $i),
                            array_reverse(array_slice($route, $i, $j - $i + 1)),
                            array_slice($route, $j + 1)
                        );
                        $improved = true;
                    }
                }
            }
        }

        $total = 0.0;
        for ($i = 0; $i < count($route) - 1; $i++) {
            $total += $distanceMatrix[$route[$i]][$route[$i + 1]];
        }

        return new TspResult(
            orderedNodeIndices: $route,
            totalDistanceKm: round($total, 2),
            totalDurationMin: (int) ceil(($total / 30) * 60),
        );
    }
}

class TspSolverService
{
    public function __construct(private readonly OsrmService $osrm) {}

    public function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadiusKm * asin(sqrt($a));
    }

    public function buildDistanceMatrix(array $nodes): array
    {
        $n = count($nodes);
        $matrix = array_fill(0, $n, array_fill(0, $n, 0.0));

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $d = $this->haversine($nodes[$i]['lat'], $nodes[$i]['lng'], $nodes[$j]['lat'], $nodes[$j]['lng']);
                $matrix[$i][$j] = $d;
                $matrix[$j][$i] = $d;
            }
        }

        return $matrix;
    }

    protected function selectStrategy(int $nodeCount): TspSolverStrategy
    {
        $maxBruteForce = config('soto.tsp_brute_force_max_nodes', 10);

        return $nodeCount <= $maxBruteForce
            ? new BruteForceTspStrategy
            : new NearestNeighborTwoOptStrategy;
    }

    public function solveAndPersist(array $depot): ?Route
    {
        $threshold = (int) Setting::get('tsp_threshold', config('soto.default_threshold', 80));

        $eligibleMachines = Machine::where(function ($q) use ($threshold) {
            $q->where('kapasitas_terkini', '>=', $threshold)
              ->orWhereColumn('kapasitas_terkini', '>=', 'threshold_capacity');
        })->get();

        if ($eligibleMachines->isEmpty()) {
            return null;
        }

        // Susun daftar node: depot (index 0) + mesin-mesin eligible
        $nodes = [['lat' => (float) $depot['lat'], 'lng' => (float) $depot['lng']]];
        foreach ($eligibleMachines as $machine) {
            $nodes[] = ['lat' => (float) $machine->latitude, 'lng' => (float) $machine->longitude];
        }

        // === Bangun Matriks Jarak via OSRM (fallback: Haversine) ===
        ['matrix' => $matrix, 'source' => $distanceSource] = $this->osrm->buildDistanceMatrix($nodes, $this);

        // === Solve TSP ===
        $strategy = $this->selectStrategy(count($nodes));
        $result   = $strategy->solve($matrix, depotIndex: 0);

        // === Ambil Polyline Jalan Aspal via OSRM Route API ===
        // Susun urutan node yang dikunjungi (termasuk balik ke depot)
        $orderedNodes = array_map(
            fn ($idx) => $nodes[$idx],
            $result->orderedNodeIndices
        );
        $roadPolyline = $this->osrm->getRoutePolyline($orderedNodes);

        // === Simpan Route ke DB ===
        $route = Route::create([
            'total_distance_km' => $result->totalDistanceKm,
            'total_duration_min' => $result->totalDurationMin,
            'status'            => 'pending',
            'route_geometry'    => $roadPolyline,
            'distance_source'   => $distanceSource,
            'created_at'        => now(),
        ]);

        foreach ($result->orderedNodeIndices as $order => $nodeIdx) {
            if ($nodeIdx === 0) {
                continue; // skip depot
            }
            $machine = $eligibleMachines[$nodeIdx - 1];
            RouteStop::create([
                'route_id'  => $route->id,
                'machine_id' => $machine->id,
                'urutan'    => $order,
            ]);
        }

        return $route->load('stops.machine');
    }
}
