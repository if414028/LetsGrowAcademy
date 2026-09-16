<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscriber
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->hasActiveSubscription()) {
            return redirect()
                ->route('subscriptions.index')
                ->with('error', 'Selling Kit hanya dapat diakses oleh subscriber aktif.');
        }

        return $next($request);
    }
}
