<?php

namespace App\DTO\Auth;

final class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}

