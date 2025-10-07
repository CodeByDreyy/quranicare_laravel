<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null): Response
    {
        $admin = $request->user();

        if (!$admin || !($admin instanceof \App\Models\Admin)) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 401);
        }

        if (!$admin->is_active) {
            return response()->json(['error' => 'Admin account is deactivated.'], 403);
        }

        if ($permission && !$admin->hasPermission($permission)) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }

        return $next($request);
    }
}
