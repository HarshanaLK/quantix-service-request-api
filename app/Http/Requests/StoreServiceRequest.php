<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\ServiceRequest::class) ?? false;
    }
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => ['required', 'in:it_support,equipment,maintenance,access,other'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'submit' => ['sometimes', 'boolean']
        ];
    }
    public function messages(): array
    {
        return [
            'title.min' => 'The title must contain at least 5 characters.',
            'description.min' => 'Please provide at least 10 characters of detail.'
        ];
    }
}
