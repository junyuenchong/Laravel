<?php

namespace App\CQRS\Auth\Handlers;

use App\CQRS\Auth\Queries\MeQuery;
use App\Services\AuthService;

final class MeHandler
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function handle(MeQuery $query): array
    {
        return $this->service->me($query->user);
    }
}

