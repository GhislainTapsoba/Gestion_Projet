<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|JsonResponse
    {
        if (!$request->user() || !$request->user()->hasRole($role)) {
            return response()->json([
                'message' => 'Access denied. Required role: ' . $role
            ], 403);
        }

        return $next($request);
    }
}
