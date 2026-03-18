<?php

namespace App\CQRS\Auth\Commands;

use App\Models\User;

final class UpdateMeCommand
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly User $user,
        public readonly array $data,
    ) {}
}

