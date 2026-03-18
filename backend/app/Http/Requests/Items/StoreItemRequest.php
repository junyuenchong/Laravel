<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

final class StoreItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:items,sku'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

