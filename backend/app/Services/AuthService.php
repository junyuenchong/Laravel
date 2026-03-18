<?php

namespace App\Services;

use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\RegisterDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /**
     * @throws \RuntimeException when credentials are invalid.
     */
    public function login(LoginDTO $dto): User
    {
        $user = $this->users->findByEmail($dto->email);
        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new \RuntimeException('Invalid credentials.');
        }

        return $user;
    }

    public function register(RegisterDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => strtolower($dto->email),
            'password' => Hash::make($dto->password),
        ]);
    }

    /**
     * Cache the "me" payload briefly.
     */
    public function me(User $user): array
    {
        $cacheKey = 'users:me:'.$user->id;

        return Cache::remember($cacheKey, now()->addMinutes(1), function () use ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        });
    }

    public function updateMe(User $user, array $validated): array
    {
        $user->fill($validated)->save();
        Cache::forget('users:me:'.$user->id);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    public function attachLoginCookie(User $user, int $status = 200)
    {
        Auth::login($user);

        $now = time();
        $ttlSeconds = (int) env('JWT_TTL_SECONDS', 3600);

        $jwt = JWT::encode([
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ], $this->jwtSecret(), 'HS256');

        $cookie = Cookie::make(
            'access_token',
            $jwt,
            (int) ceil($ttlSeconds / 60),
            '/',
            null,
            $this->isSecure(),
            true,  // HttpOnly
            false,
            $this->sameSite()
        );

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], $status)->withCookie($cookie);
    }

    public function logoutResponse()
    {
        Auth::logout();
        $cookie = Cookie::forget('access_token');
        return response()->json([])->withCookie($cookie);
    }

    private function jwtSecret(): string
    {
        $secret = config('app.key');
        $env = env('JWT_SECRET');
        if (is_string($env) && $env !== '') {
            $secret = $env;
        }

        if (is_string($secret) && str_starts_with($secret, 'base64:')) {
            return base64_decode(substr($secret, 7)) ?: '';
        }

        return (string) $secret;
    }

    private function isSecure(): bool
    {
        return (bool) env('COOKIE_SECURE', false);
    }

    private function sameSite(): string
    {
        return (string) env('COOKIE_SAMESITE', 'lax');
    }
}

