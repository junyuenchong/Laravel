<?php

namespace App\CQRS\Items\Commands;

use App\Models\Item;

final class DeleteItemCommand
{
    public function __construct(
        public readonly Item $item,
    ) {}
}

