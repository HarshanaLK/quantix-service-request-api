<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;

class RequestActivityLogger
{
    public function __construct(private readonly RequestActivityRepositoryInterface $activities) {}

    /** Record an auditable change in the same transaction as the original write. */
    public function log(
        ServiceRequest $request,
        User $actor,
        string $action,
        ?RequestStatus $from,
        RequestStatus $to,
        ?string $comment = null,
        ?array $details = null,
    ): RequestActivity {
        return $this->activities->create([
            'service_request_id' => $request->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'comment' => $comment,
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
