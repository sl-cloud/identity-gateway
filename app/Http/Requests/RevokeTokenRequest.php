<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'token_type_hint' => ['sometimes', 'string', 'in:access_token,refresh_token'],
        ];
    }
}
