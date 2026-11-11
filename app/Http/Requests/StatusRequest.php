<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operate', $this->route('serviceRequest')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:in_progress,completed'],
            'comment' => ['nullable', 'string', 'max:2000']
        ];
    }
}
