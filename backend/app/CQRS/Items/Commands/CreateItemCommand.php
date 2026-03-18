<?php

namespace App\CQRS\Items\Commands;

final class CreateItemCommand
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly array $data,
    ) {}
}

