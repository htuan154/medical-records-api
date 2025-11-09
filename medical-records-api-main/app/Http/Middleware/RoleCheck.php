<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    public function handle(Request $request, Closure $next, string $requiredPerm): Response
    {
        $auth = $request->attributes->get('auth');
        $perms = $auth->u->perms ?? [];
        if (!in_array($requiredPerm, $perms, true)) {
            return response()->json(['error'=>'forbidden','message'=>'Permission denied'], 403);
        }
        return $next($request);
    }
}
