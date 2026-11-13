<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCrm
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        if (config('crm.require_https') && ! $request->isSecure()) {
            return response()->json(['message' => 'HTTPS is required.'], 403);
        }

        $token = (string) config('crm.api_token');
        if (strlen($token) < 32) {
            return response()->json(['message' => 'CRM API is not configured.'], 503);
        }

        if (! hash_equals(hash('sha256', $token), hash('sha256', $request->bearerToken() ?? ''))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
