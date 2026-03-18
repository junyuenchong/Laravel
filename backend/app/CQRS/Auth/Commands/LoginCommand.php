<?php

namespace App\CQRS\Auth\Commands;

use App\DTO\Auth\LoginDTO;

final class LoginCommand
{
    public function __construct(
        public readonly LoginDTO $dto,
    ) {}
}

