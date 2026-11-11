<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Handles request use-cases, keeping HTTP details out of business logic. */
class ServiceRequestService
{
    public function __construct(
        private readonly ServiceRequestRepositoryInterface $requests,
        private readonly RequestActivityRepositoryInterface $activities,
        private readonly RequestActivityLogger $audit,
        private readonly RequestWorkflow $workflow,
    ) {}

    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', ServiceRequest::class);

        return $this->requests->paginateVisibleTo($actor, $filters);
    }

    public function details(User $actor, ServiceRequest $request): ServiceRequest
    {
        Gate::forUser($actor)->authorize('view', $request);

        return $this->requests->load($request, ['employee', 'assignee', 'comments', 'activities']);
    }

    public function activity(User $actor, ServiceRequest $request): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('view', $request);

        return $this->activities->paginateForRequest($request, 20);
    }

    public function create(User $employee, array $data): ServiceRequest
    {
        Gate::forUser($employee)->authorize('create', ServiceRequest::class);

        // Validate before creating so an invalid immediate submission does not
        // leave behind a draft that the user did not ask for.
        if (($data['submit'] ?? false) && ! $employee->manager?->isRole(UserRole::Manager)) {
            throw ValidationException::withMessages([
                'manager' => ['A valid reporting manager is required before submitting.'],
            ]);
        }

        $created = DB::transaction(function () use ($employee, $data) {
            $item = $this->requests->create([
                'employee_id' => $employee->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => RequestStatus::Draft,
            ]);

            $this->audit->log($item, $employee, 'created', null, RequestStatus::Draft);

            return $item;
        });

        if ($data['submit'] ?? false) {
            $created = $this->workflow->transition($created, $employee, 'submit');
        }

        return $this->requests->load($created, ['employee', 'assignee', 'comments', 'activities']);
    }

    public function update(User $actor, ServiceRequest $request, array $data): ServiceRequest
    {
        return DB::transaction(function () use ($actor, $request, $data) {
            $locked = $this->requests->findForUpdateOrFail($request->id);
            Gate::forUser($actor)->authorize('update', $locked);

            $locked->fill($data);
            $changes = [];
            foreach ($locked->getDirty() as $field => $value) {
                $changes[$field] = ['from' => $locked->getRawOriginal($field), 'to' => $value];
            }

            if ($changes !== []) {
                $this->requests->save($locked);
                $this->audit->log($locked, $actor, 'updated', $locked->status, $locked->status, details: $changes);
            }

            return $this->requests->load($locked, ['employee', 'assignee']);
        });
    }
}
