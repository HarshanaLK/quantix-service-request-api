<?php

namespace App\Repositories\Eloquent;

use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RequestActivityRepository extends BaseRepository implements RequestActivityRepositoryInterface
{
    public function __construct(RequestActivity $model)
    {
        parent::__construct($model);
    }

    public function create(array $attributes): RequestActivity
    {
        /** @var RequestActivity $item */
        $item = $this->createModel($attributes);

        return $item;
    }

    public function paginateForRequest(ServiceRequest $request, int $perPage = 20): LengthAwarePaginator
    {
        // The relationship eager-loads each actor to avoid N+1 queries.
        return $request->activities()->paginate($perPage);
    }
}
