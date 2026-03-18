<?php

namespace App\CQRS\Auth\Queries;

use App\Models\User;

final class MeQuery
{
    public function __construct(
        public readonly User $user,
    ) {}
}

