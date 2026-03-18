<?php

namespace App\CQRS\Items\Handlers;

use App\CQRS\Items\Queries\ListItemsQuery;
use App\DTO\CursorPageDTO;
use App\Repositories\ItemRepository;
use Illuminate\Support\Facades\Cache;

final class ListItemsHandler
{
    public function __construct(
        private readonly ItemRepository $items,
    ) {}

    public function handle(ListItemsQuery $query): CursorPageDTO
    {
        $dto = $query->dto;
        $cacheKey = $this->itemsIndexCacheKey($dto);

        $payload = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($dto) {
            $q = $this->items->buildIndexQuery($dto);
            $paginator = $q->cursorPaginate($dto->perPage, ['*'], 'cursor', $dto->cursor);
            return CursorPageDTO::fromPaginator($paginator, $dto->perPage)->toArray();
        });

        return CursorPageDTO::fromArray($payload);
    }

    private function itemsCacheVersionKey(): string
    {
        return 'items:cache_version';
    }

    private function itemsIndexCacheKey($dto): string
    {
        $cursor = (string) ($dto->cursor ?? '');
        $isActive = is_bool($dto->isActive) ? (int) $dto->isActive : 'any';
        $v = (int) Cache::get($this->itemsCacheVersionKey(), 1);

        return implode(':', [
            'items:index',
            'v'.$v,
            'u'.$dto->userId,
            'q'.sha1((string) ($dto->q ?? '')),
            'active'.$isActive,
            'pp'.$dto->perPage,
            'c'.sha1($cursor),
        ]);
    }
}

