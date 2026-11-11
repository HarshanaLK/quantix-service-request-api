<?php

namespace Tests\Unit;

use App\Enums\RequestStatus;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;
use App\Services\RequestActivityLogger;
use Mockery;
use Tests\TestCase;

class RequestActivityLoggerTest extends TestCase
{
    public function test_audit_logger_passes_request_actor_and_statuses_to_repository(): void
    {
        $request = new ServiceRequest;
        $request->id = 42;
        $actor = new User;
        $actor->id = 11;

        $repository = Mockery::mock(RequestActivityRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->withArgs(function (array $attributes): bool {
            return $attributes['service_request_id'] === 42
                && $attributes['actor_id'] === 11
                && $attributes['action'] === 'approve'
                && $attributes['from_status'] === 'pending'
                && $attributes['to_status'] === 'approved'
                && $attributes['comment'] === 'Approved.'
                && isset($attributes['created_at']);
        })->andReturn(new RequestActivity);

        $logger = new RequestActivityLogger($repository);
        $this->assertInstanceOf(RequestActivity::class, $logger->log(
            $request,
            $actor,
            'approve',
            RequestStatus::Pending,
            RequestStatus::Approved,
            'Approved.',
        ));
    }
}
