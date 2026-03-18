<?php

namespace App\Repositories;

use App\DTO\Items\ItemsIndexDTO;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class ItemRepository
{
    public function buildIndexQuery(ItemsIndexDTO $dto): Builder
    {
        $query = Item::query()
            ->select(['id', 'name', 'sku', 'description', 'price_cents', 'is_active', 'created_at', 'updated_at'])
            ->orderByDesc('is_active')
            ->orderBy('id');

        if ($dto->isActive !== null) {
            $query->where('is_active', $dto->isActive);
        }

        if (is_string($dto->q) && $dto->q !== '') {
            $q = $dto->q;
            $supportsFullText = $this->supportsFullText();

            $query->where(function ($sub) use ($q, $supportsFullText) {
                $sub->where('sku', $q)
                    ->when($supportsFullText, fn ($q2) => $q2->orWhereFullText('name', $q))
                    ->orWhere('name', 'like', '%'.$q.'%');
            });
        }

        return $query;
    }

    private function supportsFullText(): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        return in_array($driver, ['mysql', 'mariadb'], true);
    }
}

