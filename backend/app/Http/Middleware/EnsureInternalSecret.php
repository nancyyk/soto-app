<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the shared secret header used by Node-RED for internal API calls.
 */
class EnsureInternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('soto.internal_api_secret');

        if (empty($secret) || $request->header('X-Internal-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
