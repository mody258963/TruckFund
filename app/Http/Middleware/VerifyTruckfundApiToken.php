<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTruckfundApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('truckfund.api_token');

        if (! $token || $request->bearerToken() !== $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
