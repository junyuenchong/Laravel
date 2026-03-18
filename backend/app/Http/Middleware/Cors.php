<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = config('app.frontend_url');

        if ($request->getMethod() === 'OPTIONS') {
            $response = response()->noContent(204);
        } else {
            $response = $next($request);
        }

        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-XSRF-TOKEN, X-CSRF-TOKEN, Authorization, Accept');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}

