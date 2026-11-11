<?php

namespace Tests\Feature;

use App\Enums\{RequestStatus, UserRole};
use App\Models\{ServiceRequest, User};
use App\Notifications\RequestChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;
    private function user(UserRole $role, ?int $manager = null): User
    {
        return User::factory()->create(['role' => $role, 'manager_id' => $manager]);
    }
    public function test_employee_can_create_and_submit_request(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager->id);
        $this->actingAs($employee)->postJson('/api/requests', ['title' => 'Laptop access issue', 'description' => 'I cannot access the company laptop.', 'category' => 'it_support', 'submit' => true])->assertCreated()->assertJsonPath('data.status', 'pending');
    }
    public function test_unrelated_manager_cannot_decide_request(): void
    {
        $manager = $this->user(UserRole::Manager);
        $other = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager->id);
        $request = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => RequestStatus::Pending]);
        $this->actingAs($other)->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve', 'comment' => 'Looks good'])->assertForbidden();
    }
    public function test_employee_cannot_edit_pending_request(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager->id);
        $request = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => RequestStatus::Pending]);
        $this->actingAs($employee)->putJson("/api/requests/{$request->id}", ['title' => 'Updated title'])->assertForbidden();
    }
    public function test_invalid_transition_returns_422(): void
    {
        $ops = $this->user(UserRole::Operations);
        $employee = $this->user(UserRole::Employee);
        $request = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => RequestStatus::Completed]);
        $this->actingAs($ops)->postJson("/api/requests/{$request->id}/status", ['status' => 'in_progress'])->assertUnprocessable()->assertJsonValidationErrors('status');
    }
    public function test_status_change_dispatches_queued_notification(): void
    {
        Notification::fake();
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager->id);
        $ops = $this->user(UserRole::Operations);
        $request = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => RequestStatus::Approved]);
        $this->actingAs($ops)->postJson("/api/requests/{$request->id}/assign", ['assigned_to' => $ops->id])->assertOk();
        Notification::assertSentTo($employee, RequestChangedNotification::class);
    }
    public function test_employee_cannot_assign_work(): void
    {
        $employee = $this->user(UserRole::Employee);
        $request = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => RequestStatus::Approved]);
        $this->actingAs($employee)->postJson("/api/requests/{$request->id}/assign", ['assigned_to' => $employee->id])->assertForbidden();
    }
}
