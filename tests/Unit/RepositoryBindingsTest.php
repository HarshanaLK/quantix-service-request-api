<?php

namespace Tests\Unit;

use App\Repositories\Base\BaseRepository;
use App\Repositories\Base\EloquentRepositoryInterface;
use App\Repositories\Eloquent\RequestActivityRepository;
use App\Repositories\Eloquent\RequestCommentRepository;
use App\Repositories\Eloquent\ServiceRequestRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\RequestActivityRepositoryInterface;
use App\Repositories\Interfaces\RequestCommentRepositoryInterface;
use App\Repositories\Interfaces\ServiceRequestRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\AuthService;
use App\Services\RequestActivityLogger;
use App\Services\RequestCommentService;
use App\Services\RequestWorkflow;
use App\Services\ServiceRequestService;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class RepositoryBindingsTest extends TestCase
{
    public function test_repository_interfaces_resolve_to_eloquent_implementations(): void
    {
        $bindings = [
            ServiceRequestRepositoryInterface::class => ServiceRequestRepository::class,
            UserRepositoryInterface::class => UserRepository::class,
            RequestActivityRepositoryInterface::class => RequestActivityRepository::class,
            RequestCommentRepositoryInterface::class => RequestCommentRepository::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $instance = app($interface);
            $this->assertInstanceOf($implementation, $instance);
            $this->assertInstanceOf($interface, $instance);
            $this->assertInstanceOf(EloquentRepositoryInterface::class, $instance);
            $this->assertInstanceOf(BaseRepository::class, $instance);
        }
    }

    public function test_services_depend_on_repository_interfaces_instead_of_eloquent_repositories(): void
    {
        foreach ([
            ServiceRequestService::class,
            RequestWorkflow::class,
            RequestCommentService::class,
            RequestActivityLogger::class,
            AuthService::class,
        ] as $serviceClass) {
            $constructor = (new ReflectionClass($serviceClass))->getConstructor();
            $this->assertNotNull($constructor);
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                $this->assertInstanceOf(ReflectionNamedType::class, $type);
                $this->assertFalse(str_starts_with($type->getName(), 'App\\Repositories\\Eloquent\\'));
            }
            $this->assertNotNull(app($serviceClass));
        }
    }
}
