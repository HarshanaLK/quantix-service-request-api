<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comment', $this->route('serviceRequest')) ?? false;
    }
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:2000']
        ];
    }
}
