<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to specific user roles.
 * Usage: ->middleware("role:admin") or ->middleware("role:admin,petugas")
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(["error" => "Unauthenticated"], 401)
                : redirect()->route("login");
        }

        $allowedRoles = array_map(fn(string $r) => UserRole::from($r), $roles);

        if (!in_array($user->role, $allowedRoles)) {
            return $request->expectsJson()
                ? response()->json(["error" => "Forbidden"], 403)
                : abort(403, "Akses ditolak.");
        }

        return $next($request);
    }
}
