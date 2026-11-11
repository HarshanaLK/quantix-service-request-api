<?php

namespace App\Repositories\Eloquent;

use App\Models\RequestComment;
use App\Models\ServiceRequest;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Interfaces\RequestCommentRepositoryInterface;

class RequestCommentRepository extends BaseRepository implements RequestCommentRepositoryInterface
{
    public function __construct(RequestComment $model)
    {
        parent::__construct($model);
    }

    public function createForRequest(ServiceRequest $request, int $authorId, string $body): RequestComment
    {
        return $request->comments()->create([
            'user_id' => $authorId,
            'body' => $body,
        ]);
    }
}
