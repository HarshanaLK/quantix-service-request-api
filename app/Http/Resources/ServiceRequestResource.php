<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status->value,
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'email' => $this->employee->email,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),
            'comments' => $this->whenLoaded('comments', fn () => RequestCommentResource::collection($this->comments)),
            'activities' => $this->whenLoaded('activities', fn () => RequestActivityResource::collection($this->activities)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
