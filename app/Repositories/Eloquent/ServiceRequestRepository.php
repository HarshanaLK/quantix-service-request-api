<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceRequestRepository extends BaseRepository implements ServiceRequestRepositoryInterface
{
    public function __construct(ServiceRequest $model)
    {
        parent::__construct($model);
    }

    public function paginateVisibleTo(User $user, array $filters): LengthAwarePaginator
    {
        // Scope before filtering/pagination; do not leak other users' requests.
        $query = $this->query()->with(['employee', 'assignee']);

        if ($user->role === UserRole::Employee) {
            $query->where('employee_id', $user->id);
        } elseif ($user->role === UserRole::Manager) {
            $query->whereHas('employee', fn ($query) => $query->where('manager_id', $user->id));
        } elseif ($user->role !== UserRole::Operations) {
            abort(403);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && trim($filters['search']) !== '') {
            $search = trim($filters['search']);
            $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return $query
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10));
    }

    public function findOrFail(int $id, array $relations = []): ServiceRequest
    {
        /** @var ServiceRequest $item */
        $item = $this->requireById($id, $relations);

        return $item;
    }

    public function findForUpdateOrFail(int $id): ServiceRequest
    {
        /** @var ServiceRequest $item */
        $item = $this->query()->lockForUpdate()->findOrFail($id);

        return $item;
    }

    public function create(array $attributes): ServiceRequest
    {
        /** @var ServiceRequest $item */
        $item = $this->createModel($attributes);

        return $item;
    }

    public function save(ServiceRequest $serviceRequest): ServiceRequest
    {
        $this->saveModel($serviceRequest);

        return $serviceRequest;
    }

    public function load(ServiceRequest $serviceRequest, array $relations): ServiceRequest
    {
        $this->loadModel($serviceRequest, $relations);

        return $serviceRequest;
    }
}
