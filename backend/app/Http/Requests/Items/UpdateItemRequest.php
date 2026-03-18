<?php

namespace App\Http\Requests\Items;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateItemRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:190'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('items', 'sku')->ignore($item->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'price_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}

