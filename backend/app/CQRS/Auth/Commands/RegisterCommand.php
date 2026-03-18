<?php

namespace App\CQRS\Auth\Commands;

use App\DTO\Auth\RegisterDTO;

final class RegisterCommand
{
    public function __construct(
        public readonly RegisterDTO $dto,
    ) {}
}

