<?php

namespace App\Services;

use App\Models\RequestComment;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\Interfaces\RequestCommentRepositoryInterface;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RequestCommentService
{
    public function __construct(
        private readonly ServiceRequestRepositoryInterface $requests,
        private readonly RequestCommentRepositoryInterface $comments,
        private readonly RequestActivityLogger $audit,
    ) {}

    public function add(User $actor, ServiceRequest $request, string $body): RequestComment
    {
        return DB::transaction(function () use ($actor, $request, $body) {
            $locked = $this->requests->findForUpdateOrFail($request->id);
            Gate::forUser($actor)->authorize('comment', $locked);

            $comment = $this->comments->createForRequest($locked, $actor->id, $body);
            $this->audit->log($locked, $actor, 'commented', $locked->status, $locked->status, $body);

            return $comment->load('user');
        });
    }
}
