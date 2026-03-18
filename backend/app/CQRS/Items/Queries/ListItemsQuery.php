<?php

namespace App\CQRS\Items\Queries;

use App\DTO\Items\ItemsIndexDTO;

final class ListItemsQuery
{
    public function __construct(
        public readonly ItemsIndexDTO $dto,
    ) {}
}

