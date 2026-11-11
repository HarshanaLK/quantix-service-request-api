<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexServiceRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ServiceRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(array_column(RequestStatus::cases(), 'value'))],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', Rule::in(['created_at', 'updated_at', 'title', 'priority'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
