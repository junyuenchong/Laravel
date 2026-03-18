<?php

namespace App\CQRS\Items\Handlers;

use App\CQRS\Items\Commands\CreateItemCommand;
use App\Models\Item;
use Illuminate\Support\Facades\Cache;

final class CreateItemHandler
{
    public function handle(CreateItemCommand $command): Item
    {
        $item = Item::create($command->data);
        $this->bumpItemsCacheVersion();
        return $item;
    }

    private function itemsCacheVersionKey(): string
    {
        return 'items:cache_version';
    }

    private function bumpItemsCacheVersion(): void
    {
        Cache::increment($this->itemsCacheVersionKey());
    }
}

