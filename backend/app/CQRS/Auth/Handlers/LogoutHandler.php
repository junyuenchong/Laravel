<?php

namespace App\CQRS\Auth\Handlers;

use App\CQRS\Auth\Commands\LogoutCommand;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class LogoutHandler
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function handle(LogoutCommand $command): JsonResponse
    {
        return $this->service->logoutResponse();
    }
}

