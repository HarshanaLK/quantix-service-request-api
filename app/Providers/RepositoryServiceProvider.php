<?php

namespace App\Providers;

use App\Repositories\Eloquent\RequestActivityRepository;
use App\Repositories\Eloquent\RequestCommentRepository;
use App\Repositories\Eloquent\ServiceRequestRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;
use App\Repositories\Interfaces\RequestCommentRepositoryInterface;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceRequestRepositoryInterface::class, ServiceRequestRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RequestActivityRepositoryInterface::class, RequestActivityRepository::class);
        $this->app->bind(RequestCommentRepositoryInterface::class, RequestCommentRepository::class);
    }
}
