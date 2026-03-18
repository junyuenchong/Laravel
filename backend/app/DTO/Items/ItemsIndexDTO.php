<?php

namespace App\DTO\Items;

use Illuminate\Http\Request;

final class ItemsIndexDTO
{
    public function __construct(
        public readonly ?string $q,
        public readonly ?bool $isActive,
        public readonly int $perPage,
        public readonly ?string $cursor,
        public readonly string|int $userId,
    ) {}

    public static function fromRequest(Request $request, array $validated): self
    {
        $perPage = (int) ($validated['per_page'] ?? 20);
        $q = isset($validated['q']) ? (string) $validated['q'] : null;

        return new self(
            q: is_string($q) && $q !== '' ? $q : null,
            isActive: array_key_exists('is_active', $validated) ? (bool) $request->boolean('is_active') : null,
            perPage: $perPage,
            cursor: $request->query('cursor') ? (string) $request->query('cursor') : null,
            userId: optional($request->user())->id ?? 'guest',
        );
    }
}

