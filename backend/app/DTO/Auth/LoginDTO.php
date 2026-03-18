<?php

namespace App\DTO\Auth;

final class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}

