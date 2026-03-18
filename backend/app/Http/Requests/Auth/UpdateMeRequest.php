<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}

