<?php

namespace Tests\Unit;

use App\Enums\RequestStatus;
use App\Models\{ServiceRequest, User};
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;
    public function test_completed_request_is_final(): void
    {
        $user = User::factory()->create();
        $request = ServiceRequest::factory()->create(['employee_id' => $user->id, 'status' => RequestStatus::Completed]);
        $this->expectException(ValidationException::class);
        app(RequestWorkflow::class)->transition($request, $user, 'submit');
    }
}
