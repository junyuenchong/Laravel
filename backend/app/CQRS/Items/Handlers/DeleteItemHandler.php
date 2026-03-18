<?php

namespace App\CQRS\Items\Handlers;

use App\CQRS\Items\Commands\DeleteItemCommand;
use Illuminate\Support\Facades\Cache;

final class DeleteItemHandler
{
    public function handle(DeleteItemCommand $command): void
    {
        $command->item->delete();
        $this->bumpItemsCacheVersion();
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

