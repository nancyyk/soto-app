<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Device;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Device-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'Missing X-Device-Key header'], 401);
        }

        $device = Device::where('api_key', $apiKey)->where('is_active', true)->first();

        if (!$device) {
            return response()->json(['message' => 'Invalid or inactive device API key'], 401);
        }

        $device->update(['last_seen_at' => now()]);

        return $next($request);
    }
}