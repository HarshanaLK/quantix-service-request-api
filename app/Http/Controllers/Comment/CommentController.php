<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\RequestCommentResource;
use App\Models\ServiceRequest;
use App\Services\RequestCommentService;

class CommentController extends Controller
{
    public function store(CommentRequest $request, ServiceRequest $serviceRequest, RequestCommentService $service)
    {
        $comment = $service->add($request->user(), $serviceRequest, $request->validated('body'));

        return (new RequestCommentResource($comment))
            ->additional(['success' => true, 'message' => 'Comment added.'])
            ->response()->setStatusCode(201);
    }
}
