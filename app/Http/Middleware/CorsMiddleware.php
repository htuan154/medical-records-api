<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin', '*');
        $allowOrigin = $origin && $origin !== 'null' ? $origin : '*';

        // Handle preflight requests
        if ($request->getMethod() === "OPTIONS") {
            return response('')
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-TOKEN, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', $allowOrigin === '*' ? 'false' : 'true')
                ->header('Access-Control-Max-Age', '3600');
        }

        $response = $next($request);

        // Add CORS headers to all responses
        return $response
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-TOKEN, Accept, Origin')
            ->header('Access-Control-Allow-Credentials', $allowOrigin === '*' ? 'false' : 'true')
            ->header('Access-Control-Expose-Headers', 'Authorization');
    }
}
