<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function user(UserRole $role, ?User $manager = null): User
    {
        return User::factory()->create(['role' => $role, 'manager_id' => $manager?->id]);
    }

    public function test_seeded_example_email_domain_is_accepted_and_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ALEX@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'alex@example.com']);

        $this->postJson('/api/login', [
            'email' => 'alex@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_protected_endpoint_returns_json_401_without_accept_header(): void
    {
        $this->get('/api/requests')
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json');
    }

    public function test_owner_can_view_submit_and_edit_only_allowed_states(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $serviceRequest = ServiceRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($employee)->getJson("/api/requests/{$serviceRequest->id}")
            ->assertOk()->assertJsonPath('data.status', 'draft');

        $this->putJson("/api/requests/{$serviceRequest->id}", ['title' => 'A revised request title'])
            ->assertOk();

        $this->postJson("/api/requests/{$serviceRequest->id}/submit")
            ->assertOk()->assertJsonPath('data.status', 'pending');

        $this->putJson("/api/requests/{$serviceRequest->id}", ['title' => 'Forbidden revision'])
            ->assertForbidden();
    }

    public function test_unrelated_employee_cannot_view_request_or_comment(): void
    {
        $owner = $this->user(UserRole::Employee);
        $other = $this->user(UserRole::Employee);
        $serviceRequest = ServiceRequest::factory()->create(['employee_id' => $owner->id]);

        $this->actingAs($other)->getJson("/api/requests/{$serviceRequest->id}")->assertForbidden();
        $this->postJson("/api/requests/{$serviceRequest->id}/comments", ['body' => 'Not authorized'])
            ->assertForbidden();
    }

    public function test_manager_return_and_employee_resubmit(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => RequestStatus::Pending,
        ]);

        $this->actingAs($manager)->postJson("/api/requests/{$serviceRequest->id}/decision", [
            'decision' => 'return',
            'comment' => 'Please add the serial number.',
        ])->assertOk()->assertJsonPath('data.status', 'returned');

        $this->actingAs($employee)->putJson("/api/requests/{$serviceRequest->id}", [
            'description' => 'Updated laptop details and serial number.',
        ])->assertOk();

        $this->postJson("/api/requests/{$serviceRequest->id}/submit")
            ->assertOk()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('request_activities', 3); // return, edit and resubmit
        $this->assertDatabaseCount('request_comments', 1);
    }

    public function test_complete_lifecycle_and_terminal_state(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $operator = $this->user(UserRole::Operations);
        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => RequestStatus::Pending,
        ]);
        $id = $serviceRequest->id;

        $this->actingAs($manager)->postJson("/api/requests/{$id}/decision", [
            'decision' => 'approve', 'comment' => 'Approved for service.',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $this->actingAs($operator)->postJson("/api/requests/{$id}/assign", [
            'assigned_to' => $operator->id,
        ])->assertOk()->assertJsonPath('data.status', 'assigned');

        $this->postJson("/api/requests/{$id}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->postJson("/api/requests/{$id}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $this->postJson("/api/requests/{$id}/status", ['status' => 'in_progress'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseCount('request_activities', 4);
        $this->assertDatabaseHas('service_requests', ['id' => $id, 'status' => 'completed']);
    }

    public function test_assignment_requires_operations_user(): void
    {
        $operator = $this->user(UserRole::Operations);
        $employee = $this->user(UserRole::Employee);
        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => RequestStatus::Approved,
        ]);

        $this->actingAs($operator)->postJson("/api/requests/{$serviceRequest->id}/assign", [
            'assigned_to' => $employee->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
    }

    public function test_query_parameters_are_validated(): void
    {
        $employee = $this->user(UserRole::Employee);

        $this->actingAs($employee)->getJson('/api/requests?per_page=not-an-integer')
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/requests?sort=drop_table')
            ->assertUnprocessable()->assertJsonValidationErrors('sort');
        $this->getJson('/api/requests?status=invalid')
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }
}
