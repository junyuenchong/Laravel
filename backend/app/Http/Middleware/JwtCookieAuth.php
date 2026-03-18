<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JwtCookieAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('access_token');
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key($this->jwtSecret(), 'HS256'));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userId = isset($payload->sub) ? (int) $payload->sub : 0;
        if ($userId <= 0) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function jwtSecret(): string
    {
        $secret = config('app.key');
        $env = env('JWT_SECRET');
        if (is_string($env) && $env !== '') {
            $secret = $env;
        }

        // APP_KEY can be "base64:...."
        if (is_string($secret) && str_starts_with($secret, 'base64:')) {
            return base64_decode(substr($secret, 7)) ?: '';
        }

        return (string) $secret;
    }
}

