<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Middleware for role-based access control
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!$request->user() || strtolower((string) $request->user()->role) !== strtolower($role)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
