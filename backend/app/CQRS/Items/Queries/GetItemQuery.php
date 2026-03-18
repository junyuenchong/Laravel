<?php

namespace App\CQRS\Items\Queries;

use App\Models\Item;

final class GetItemQuery
{
    public function __construct(
        public readonly Item $item,
    ) {}
}

