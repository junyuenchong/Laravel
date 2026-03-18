<?php

namespace App\CQRS\Items\Handlers;

use App\CQRS\Items\Commands\UpdateItemCommand;
use App\Models\Item;
use Illuminate\Support\Facades\Cache;

final class UpdateItemHandler
{
    public function handle(UpdateItemCommand $command): Item
    {
        $command->item->fill($command->data)->save();
        $this->bumpItemsCacheVersion();
        return $command->item;
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

