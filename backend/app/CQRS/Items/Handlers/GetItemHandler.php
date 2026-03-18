<?php

namespace App\CQRS\Items\Handlers;

use App\CQRS\Items\Queries\GetItemQuery;
use App\Models\Item;

final class GetItemHandler
{
    public function handle(GetItemQuery $query): Item
    {
        return $query->item;
    }
}

