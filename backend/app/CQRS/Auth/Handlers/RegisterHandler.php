<?php

namespace App\CQRS\Auth\Handlers;

use App\CQRS\Auth\Commands\RegisterCommand;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class RegisterHandler
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function handle(RegisterCommand $command): JsonResponse
    {
        $user = $this->service->register($command->dto);
        return $this->service->attachLoginCookie($user, 201);
    }
}

