<?php

namespace App\CQRS\Auth\Handlers;

use App\CQRS\Auth\Commands\LoginCommand;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class LoginHandler
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function handle(LoginCommand $command): JsonResponse
    {
        try {
            $user = $this->service->login($command->dto);
            return $this->service->attachLoginCookie($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

