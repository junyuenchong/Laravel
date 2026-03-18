<?php

namespace App\DTO;

use Illuminate\Pagination\CursorPaginator;

final class CursorPageDTO
{
    /**
     * @param  array<int, mixed>  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $nextCursor,
        public readonly ?string $prevCursor,
        public readonly int $perPage,
    ) {}

    public static function fromPaginator(CursorPaginator $paginator, int $perPage): self
    {
        return new self(
            data: $paginator->items(),
            nextCursor: optional($paginator->nextCursor())->encode(),
            prevCursor: optional($paginator->previousCursor())->encode(),
            perPage: $perPage,
        );
    }

    /**
     * @param  array{data: array<int, mixed>, next_cursor?: string|null, prev_cursor?: string|null, per_page: int}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            data: $payload['data'],
            nextCursor: $payload['next_cursor'] ?? null,
            prevCursor: $payload['prev_cursor'] ?? null,
            perPage: (int) $payload['per_page'],
        );
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
            'per_page' => $this->perPage,
        ];
    }
}

