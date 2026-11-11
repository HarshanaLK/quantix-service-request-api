<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('decide', $this->route('serviceRequest')) ?? false;
    }
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject,return'],
            'comment' => ['required', 'string', 'min:3', 'max:2000']
        ];
    }
}
