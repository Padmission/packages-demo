<?php

// ABOUTME: Middleware that gracefully handles missing tenant errors
// ABOUTME: Redirects to login when tenant in URL no longer exists after demo refresh

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class HandleMissingTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if route has tenant parameter
        if ($request->route()->hasParameter('tenant')) {
            $tenantId = $request->route()->parameter('tenant');

            // Check if tenant exists
            $teamModel = config('filament.tenant_model', \App\Models\Team::class);
            $tenant = $teamModel::find($tenantId);

            if (! $tenant) {
                // For demo users, redirect to login with message
                if (auth()->check() && str_contains(auth()->user()->email, 'demo_')) {
                    return redirect()->route('filament.admin.auth.login')
                        ->with('error', 'Your demo session has expired. Please login again to continue.');
                }

                // For non-demo users, show 404
                abort(404, 'Team not found.');
            }

            // Check if user can access this tenant
            if (auth()->check() && ! auth()->user()->canAccessTenant($tenant)) {
                abort(403, 'You do not have access to this team.');
            }
        }

        return $next($request);
    }
}
