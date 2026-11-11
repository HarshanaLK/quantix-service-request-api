<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('serviceRequest')) ?? false;
    }
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:5', 'max:150'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'category' => ['sometimes', 'required', 'in:it_support,equipment,maintenance,access,other'],
            'priority' => ['sometimes', 'in:low,medium,high']
        ];
    }
}
