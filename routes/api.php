<?php
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Workflow\RequestWorkflowController;
use App\Http\Controllers\ServiceRequest\ServiceRequestController;
use App\Http\Controllers\Auth\AuthController as AuthAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthAuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthAuthController::class, 'logout']);
    Route::get('/me', [AuthAuthController::class, 'me']);
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::get('/requests/{serviceRequest}/activity', [ServiceRequestController::class, 'activity']);
    Route::put('/requests/{serviceRequest}', [ServiceRequestController::class, 'update']);
    Route::post('/requests/{serviceRequest}/submit', [RequestWorkflowController::class, 'submit']);
    Route::post('/requests/{serviceRequest}/decision', [RequestWorkflowController::class, 'decision']);
    Route::post('/requests/{serviceRequest}/assign', [RequestWorkflowController::class, 'assign']);
    Route::post('/requests/{serviceRequest}/status', [RequestWorkflowController::class, 'status']);
    Route::post('/requests/{serviceRequest}/comments', [CommentController::class, 'store']);
});
