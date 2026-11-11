<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'comment' => $this->comment,
            'details' => $this->details,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
