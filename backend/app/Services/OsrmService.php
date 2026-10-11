<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsrmService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('soto.osrm_url', 'https://router.project-osrm.org'), '/');
    }

    /**
     * Bangun matriks jarak (km) antar node menggunakan OSRM Table API.
     * Node format: [['lat' => ..., 'lng' => ...], ...]
     * Fallback ke Haversine jika OSRM tidak tersedia.
     *
     * @return array{matrix: float[][], source: string}
     */
    public function buildDistanceMatrix(array $nodes, TspSolverService $fallback): array
    {
        $n = count($nodes);

        // OSRM coordinate format: lng,lat (bukan lat,lng!)
        $coords = implode(';', array_map(
            fn ($node) => number_format($node['lng'], 6, '.', '') . ',' . number_format($node['lat'], 6, '.', ''),
            $nodes
        ));

        try {
            $response = Http::withoutVerifying()->timeout(6)->get("{$this->baseUrl}/table/v1/driving/{$coords}", [
                'annotations' => 'distance',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['distances']) && count($data['distances']) === $n) {
                    // OSRM returns meters — konversi ke km
                    $matrix = array_map(
                        fn ($row) => array_map(fn ($d) => round((float) $d / 1000, 4), $row),
                        $data['distances']
                    );

                    Log::info('OSRM distance matrix fetched successfully', [
                        'nodes' => $n,
                        'source' => 'osrm',
                    ]);

                    return ['matrix' => $matrix, 'source' => 'osrm'];
                }
            }

            Log::warning('OSRM table API returned unexpected response, falling back to Haversine', [
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('OSRM table API unreachable, falling back to Haversine', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Haversine
        return [
            'matrix' => $fallback->buildDistanceMatrix($nodes),
            'source' => 'haversine',
        ];
    }

    /**
     * Ambil polyline jalan aspal dari OSRM Route API untuk urutan node.
     * Mengembalikan array [[lat, lng], ...] untuk Leaflet.
     * Fallback ke garis lurus jika OSRM tidak tersedia.
     *
     * @param  array<array{lat: float, lng: float}>  $orderedNodes
     * @return array<array{float, float}>
     */
    public function getRoutePolyline(array $orderedNodes): array
    {
        if (count($orderedNodes) < 2) {
            return array_map(fn ($n) => [(float) $n['lat'], (float) $n['lng']], $orderedNodes);
        }

        // OSRM: lng,lat order
        $coords = implode(';', array_map(
            fn ($n) => number_format((float) $n['lng'], 6, '.', '') . ',' . number_format((float) $n['lat'], 6, '.', ''),
            $orderedNodes
        ));

        try {
            $response = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/route/v1/driving/{$coords}", [
                'overview'   => 'full',
                'geometries' => 'geojson',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $geometry = $data['routes'][0]['geometry']['coordinates'] ?? null;

                if ($geometry) {
                    // GeoJSON: [lng, lat] → konversi ke Leaflet: [lat, lng]
                    return array_map(fn ($c) => [(float) $c[1], (float) $c[0]], $geometry);
                }
            }

            Log::warning('OSRM route API returned unexpected response, falling back to straight lines');
        } catch (\Throwable $e) {
            Log::warning('OSRM route API unreachable, falling back to straight lines', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: garis lurus
        return array_map(fn ($n) => [(float) $n['lat'], (float) $n['lng']], $orderedNodes);
    }
}
