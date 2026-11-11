<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Base\EloquentRepositoryInterface;

use App\Models\RequestComment;
use App\Models\ServiceRequest;

interface RequestCommentRepositoryInterface extends EloquentRepositoryInterface
{
    public function createForRequest(ServiceRequest $request, int $authorId, string $body): RequestComment;
}
