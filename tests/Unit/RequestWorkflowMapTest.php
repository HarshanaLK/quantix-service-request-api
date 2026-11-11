<?php

namespace Tests\Unit;

use App\Enums\RequestStatus;
use App\Services\RequestWorkflow;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RequestWorkflowMapTest extends TestCase
{
    public function test_every_workflow_status_is_a_valid_backed_enum_value(): void
    {
        $map = (new ReflectionClass(RequestWorkflow::class))->getReflectionConstant('ALLOWED')->getValue();

        foreach ($map as $action => $transitions) {
            $this->assertNotEmpty($transitions, $action);
            foreach ($transitions as $from => $to) {
                $this->assertInstanceOf(RequestStatus::class, RequestStatus::from($from));
                $this->assertInstanceOf(RequestStatus::class, RequestStatus::from($to));
            }
        }
    }
}
