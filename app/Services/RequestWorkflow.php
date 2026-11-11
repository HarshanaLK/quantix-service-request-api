<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\RequestChangedNotification;
use App\Repositories\Interfaces\RequestCommentRepositoryInterface;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RequestWorkflow
{
    // Single source of truth for valid state transitions.
    private const ALLOWED = [
        'submit' => ['draft' => 'pending', 'returned' => 'pending'],
        'approve' => ['pending' => 'approved'],
        'reject' => ['pending' => 'rejected'],
        'return' => ['pending' => 'returned'],
        'assign' => ['approved' => 'assigned'],
        'start' => ['assigned' => 'in_progress'],
        'complete' => ['in_progress' => 'completed'],
    ];

    public function __construct(
        private readonly ServiceRequestRepositoryInterface $requests,
        private readonly UserRepositoryInterface $users,
        private readonly RequestCommentRepositoryInterface $comments,
        private readonly RequestActivityLogger $audit,
    ) {}

    public function transition(
        ServiceRequest $request,
        User $actor,
        string $action,
        ?string $comment = null,
        ?int $assigneeId = null,
    ): ServiceRequest {
        $ability = match ($action) {
            'submit' => 'submit',
            'approve', 'reject', 'return' => 'decide',
            'assign', 'start', 'complete' => 'operate',
            default => throw ValidationException::withMessages(['status' => ['Unknown workflow action.']]),
        };

        // Lock, re-authorize, transition, and write audit in one transaction;
        // an earlier FormRequest policy check cannot protect against races.
        [$updated, $from, $to] = DB::transaction(function () use ($request, $actor, $action, $ability, $comment, $assigneeId) {
            $locked = $this->requests->findForUpdateOrFail($request->getKey());
            Gate::forUser($actor)->authorize($ability, $locked);

            $from = $locked->status;
            $next = self::ALLOWED[$action][$from->value] ?? null;
            if ($next === null) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot {$action} a request while it is {$from->value}."],
                ]);
            }

            if ($action === 'submit' && ! $locked->employee?->manager?->isRole(UserRole::Manager)) {
                throw ValidationException::withMessages([
                    'manager' => ['A valid reporting manager is required before submitting.'],
                ]);
            }

            if ($action === 'assign') {
                if ($assigneeId === null || ! $this->users->isOperationsUser($assigneeId)) {
                    throw ValidationException::withMessages([
                        'assigned_to' => ['The assignee must be an operations user.'],
                    ]);
                }
                $locked->assigned_to = $assigneeId;
            }

            $to = RequestStatus::from($next);
            $locked->status = $to;
            $this->requests->save($locked);
            $this->audit->log($locked, $actor, $action, $from, $to, $comment);

            if ($comment !== null && trim($comment) !== '') {
                $this->comments->createForRequest($locked, $actor->id, $comment);
            }

            return [$this->requests->findOrFail($locked->id, ['employee.manager', 'assignee', 'comments', 'activities']), $from->value, $to->value];
        });

        // Notification implements ShouldQueue; send after DB::transaction returns.
        $recipients = collect([$updated->employee, $updated->assignee, $updated->employee?->manager])
            ->filter()->unique('id');
        Notification::send($recipients, new RequestChangedNotification($updated, $from, $to));

        return $updated;
    }
}
