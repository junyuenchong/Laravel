<?php

namespace App\CQRS\Auth\Handlers;

use App\CQRS\Auth\Commands\UpdateMeCommand;
use App\Services\AuthService;

final class UpdateMeHandler
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function handle(UpdateMeCommand $command): array
    {
        return $this->service->updateMe($command->user, $command->data);
    }
}

