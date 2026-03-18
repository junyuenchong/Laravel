<?php

namespace App\CQRS\Items\Commands;

use App\Models\Item;

final class UpdateItemCommand
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly Item $item,
        public readonly array $data,
    ) {}
}

