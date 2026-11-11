<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Base\EloquentRepositoryInterface;

use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RequestActivityRepositoryInterface extends EloquentRepositoryInterface
{
    public function create(array $attributes): RequestActivity;

    public function paginateForRequest(ServiceRequest $request, int $perPage = 20): LengthAwarePaginator;
}
