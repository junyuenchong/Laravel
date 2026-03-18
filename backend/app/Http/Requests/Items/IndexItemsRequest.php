<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

final class IndexItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:500'],
        ];
    }
}

