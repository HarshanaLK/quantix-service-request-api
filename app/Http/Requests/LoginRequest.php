<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // DNS validation rejects valid example/test accounts and depends on network access.
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
        ];
    }
}
