<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, UserRole::cases(), true);
    }

    public function view(User $user, ServiceRequest $request): bool
    {
        return $user->isRole(UserRole::Operations)
            || $request->employee_id === $user->id
            || ($user->isRole(UserRole::Manager) && $request->employee?->manager_id === $user->id)
            || $request->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isRole(UserRole::Employee);
    }

    public function update(User $user, ServiceRequest $request): bool
    {
        return $user->isRole(UserRole::Employee)
            && $request->employee_id === $user->id
            && in_array($request->status, [RequestStatus::Draft, RequestStatus::Returned], true);
    }

    public function submit(User $user, ServiceRequest $request): bool
    {
        return $user->isRole(UserRole::Employee) && $request->employee_id === $user->id;
    }

    public function decide(User $user, ServiceRequest $request): bool
    {
        return $user->isRole(UserRole::Manager) && $request->employee?->manager_id === $user->id;
    }

    public function operate(User $user, ServiceRequest $request): bool
    {
        return $user->isRole(UserRole::Operations);
    }

    public function comment(User $user, ServiceRequest $request): bool
    {
        // Rejected and completed requests are read-only, including comments.
        return $this->view($user, $request)
            && ! in_array($request->status, [RequestStatus::Rejected, RequestStatus::Completed], true);
    }
}
