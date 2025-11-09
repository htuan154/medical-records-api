<?php

namespace App\Http\Middleware;

use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JwtMiddleware
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $auth = (string) $request->header('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            return response()->json(['error' => 'unauthorized', 'message' => 'Missing Bearer token'], 401);
        }

        try {
            $decoded = $this->jwt->verify(substr($auth, 7));
            $request->attributes->set('auth', $decoded);
        } catch (Throwable $e) {
            return response()->json(['error' => 'unauthorized', 'message' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
