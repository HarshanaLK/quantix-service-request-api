<?php

namespace App\Repositories\Interfaces;

use App\Repositories\Base\EloquentRepositoryInterface;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Data access contract for service requests; workflow rules belong to Services. */
interface ServiceRequestRepositoryInterface extends EloquentRepositoryInterface
{
    public function paginateVisibleTo(User $user, array $filters): LengthAwarePaginator;

    public function findOrFail(int $id, array $relations = []): ServiceRequest;

    /** Must be called inside DB::transaction. */
    public function findForUpdateOrFail(int $id): ServiceRequest;

    public function create(array $attributes): ServiceRequest;

    public function save(ServiceRequest $serviceRequest): ServiceRequest;

    public function load(ServiceRequest $serviceRequest, array $relations): ServiceRequest;
}
