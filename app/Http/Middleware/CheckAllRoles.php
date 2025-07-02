<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAllRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized. User not authenticated.',
                'error' => 'not_authenticated'
            ], 401);
        }

        $userRoles = explode(',', $request->user()->role);
        $hasAllRoles = collect($roles)->every(function ($role) use ($userRoles) {
            return in_array($role, $userRoles);
        });

        if (!$hasAllRoles) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.',
                'error' => 'role_unauthorized'
            ], 403);
        }

        return $next($request);
    }
} 