<?php

namespace App\Services;

use App\DTO\CursorPageDTO;
use App\DTO\Items\ItemsIndexDTO;
use App\Models\Item;
use App\Repositories\ItemRepository;
use Illuminate\Support\Facades\Cache;

final class ItemService
{
    public function __construct(
        private readonly ItemRepository $items,
    ) {}

    public function list(ItemsIndexDTO $dto): CursorPageDTO
    {
        $cacheKey = $this->itemsIndexCacheKey($dto);

        $payload = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($dto) {
            $query = $this->items->buildIndexQuery($dto);
            $paginator = $query->cursorPaginate($dto->perPage, ['*'], 'cursor', $dto->cursor);

            return CursorPageDTO::fromPaginator($paginator, $dto->perPage)->toArray();
        });

        return CursorPageDTO::fromArray($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Item
    {
        $item = Item::create($data);
        $this->bumpItemsCacheVersion();
        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Item $item, array $data): Item
    {
        $item->fill($data)->save();
        $this->bumpItemsCacheVersion();
        return $item;
    }

    public function delete(Item $item): void
    {
        $item->delete();
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

    private function itemsIndexCacheKey(ItemsIndexDTO $dto): string
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

