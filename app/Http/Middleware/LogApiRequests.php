<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user() ? $request->user()->id : null;
        $endpoint = $request->fullUrl();
        $method = $request->method();
        $timestamp = now();

        Log::info('API Request', [
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'method' => $method,
            'timestamp' => $timestamp,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
