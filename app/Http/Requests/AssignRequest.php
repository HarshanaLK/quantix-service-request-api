<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operate', $this->route('serviceRequest')) ?? false;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'operations')],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
