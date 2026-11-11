<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\RequestChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssignmentCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role, ?User $manager = null): User
    {
        return User::factory()->create(['role' => $role, 'manager_id' => $manager?->id]);
    }

    public function test_seeders_supply_three_roles_two_reports_and_ten_requests_with_activity(): void
    {
        $this->seed();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $this->assertEquals(2, $manager->directReports()->count());
        $this->assertEquals(10, ServiceRequest::count());
        $this->assertEquals(8, ServiceRequest::distinct()->count('status'));
        $this->assertEquals(0, ServiceRequest::doesntHave('activities')->count());
        $this->assertEquals(1, User::where('role', 'operations')->count());
    }

    public function test_creation_edit_and_comment_are_in_activity_history(): void
    {
        $employee = $this->user(UserRole::Employee);
        $this->actingAs($employee);
        $created = $this->postJson('/api/requests', [
            'title' => 'A new support request',
            'description' => 'A detailed issue description for support.',
            'category' => 'it_support',
        ])->assertCreated()->assertJsonPath('data.activities.0.action', 'created');
        $id = $created->json('data.id');

        $this->putJson("/api/requests/{$id}", ['title' => 'An updated support request'])
            ->assertOk();
        $this->postJson("/api/requests/{$id}/comments", ['body' => 'Additional details provided'])
            ->assertCreated();

        $activities = $this->getJson("/api/requests/{$id}/activity")
            ->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount(3, $activities->json('data'));
        $this->assertEqualsCanonicalizing(
            ['created', 'updated', 'commented'],
            array_column($activities->json('data'), 'action'),
        );
        $this->assertDatabaseHas('request_activities', ['service_request_id' => $id, 'action' => 'updated']);
    }

    public function test_only_direct_report_manager_can_view_and_decide(): void
    {
        Notification::fake();
        $manager = $this->user(UserRole::Manager);
        $otherManager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $item = ServiceRequest::factory()->create([
            'employee_id' => $employee->id, 'status' => RequestStatus::Pending,
        ]);

        $this->actingAs($otherManager)->getJson("/api/requests/{$item->id}")->assertForbidden();
        $this->postJson("/api/requests/{$item->id}/decision", [
            'decision' => 'approve', 'comment' => 'Not my report.',
        ])->assertForbidden();
        $this->actingAs($manager)->postJson("/api/requests/{$item->id}/decision", [
            'decision' => 'approve', 'comment' => 'Approved for work.',
        ])->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_role_forbidden_actions_return_403(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $operator = $this->user(UserRole::Operations);
        $item = ServiceRequest::factory()->create([
            'employee_id' => $employee->id, 'status' => RequestStatus::Pending,
        ]);
        $id = $item->id;
        $payload = ['title' => 'This is a sufficiently long title', 'description' => 'A sufficient description for validation.', 'category' => 'it_support'];

        $this->actingAs($manager)->postJson('/api/requests', $payload)->assertForbidden();
        $this->actingAs($operator)->postJson('/api/requests', $payload)->assertForbidden();
        $this->postJson("/api/requests/{$id}/decision", ['decision' => 'approve', 'comment' => 'No manager rights.'])
            ->assertForbidden();
        $this->actingAs($employee)->postJson("/api/requests/{$id}/decision", [
            'decision' => 'approve', 'comment' => 'No manager rights.',
        ])->assertForbidden();
        $this->postJson("/api/requests/{$id}/assign", ['assigned_to' => $operator->id])
            ->assertForbidden();
        $this->postJson("/api/requests/{$id}/status", ['status' => 'in_progress'])
            ->assertForbidden();
        $this->actingAs($manager)->postJson("/api/requests/{$id}/assign", [
            'assigned_to' => $operator->id,
        ])->assertForbidden();
        $this->postJson("/api/requests/{$id}/status", ['status' => 'completed'])->assertForbidden();
        $this->actingAs($operator)->putJson("/api/requests/{$id}", ['title' => 'Forbidden update'])->assertForbidden();
        $this->postJson("/api/requests/{$id}/submit")->assertForbidden();
    }

    public function test_terminal_requests_cannot_be_edited_commented_or_transitioned(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $operator = $this->user(UserRole::Operations);
        foreach ([RequestStatus::Rejected, RequestStatus::Completed] as $status) {
            $item = ServiceRequest::factory()->create(['employee_id' => $employee->id, 'status' => $status]);
            $id = $item->id;
            $this->actingAs($employee)->putJson("/api/requests/{$id}", ['title' => 'Changed after close'])->assertForbidden();
            $this->postJson("/api/requests/{$id}/comments", ['body' => 'Should not be added'])->assertForbidden();
            $this->postJson("/api/requests/{$id}/submit")->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->actingAs($manager)->postJson("/api/requests/{$id}/decision", [
                'decision' => 'approve', 'comment' => 'Not valid anymore',
            ])->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->actingAs($operator)->postJson("/api/requests/{$id}/status", [
                'status' => 'in_progress',
            ])->assertUnprocessable()->assertJsonValidationErrors('status');
        }
    }

    public function test_request_requires_manager_before_submission_and_does_not_leave_partial_record(): void
    {
        $employee = $this->user(UserRole::Employee);
        $this->actingAs($employee)->postJson('/api/requests', [
            'title' => 'Request lacking manager',
            'description' => 'This must be assigned to a manager.',
            'category' => 'it_support', 'submit' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('manager');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_search_and_role_scope_and_pagination(): void
    {
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $other = $this->user(UserRole::Employee);
        $operator = $this->user(UserRole::Operations);
        ServiceRequest::factory()->create(['employee_id' => $employee->id, 'title' => 'Printer problem']);
        ServiceRequest::factory()->create(['employee_id' => $employee->id, 'title' => 'Printer consumables']);
        ServiceRequest::factory()->create(['employee_id' => $other->id, 'title' => 'Printer confidential']);

        $this->actingAs($employee)->getJson('/api/requests?search=Printer&per_page=1&sort=title&direction=asc')
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('meta.per_page', 1);
        $this->actingAs($manager)->getJson('/api/requests')->assertJsonPath('meta.total', 2);
        $this->actingAs($operator)->getJson('/api/requests')->assertJsonPath('meta.total', 3);
    }

    public function test_status_change_pushes_a_queue_job(): void
    {
        // A sent notification fake alone does not prove a queue job was pushed.
        Queue::fake();
        $manager = $this->user(UserRole::Manager);
        $employee = $this->user(UserRole::Employee, $manager);
        $item = ServiceRequest::factory()->create(['employee_id' => $employee->id]);
        $this->actingAs($employee)->postJson("/api/requests/{$item->id}/submit")->assertOk();

        Queue::assertPushed(SendQueuedNotifications::class, function ($job): bool {
            return $job->notification instanceof RequestChangedNotification;
        });
    }

    public function test_api_missing_resources_and_unauthenticated_errors_are_json(): void
    {
        $this->get('/api/requests')->assertUnauthorized()->assertJsonPath('success', false);
        $employee = $this->user(UserRole::Employee);
        $this->actingAs($employee)->get('/api/requests/999999999')->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_employee_can_read_own_audit_but_not_another_employees_audit(): void
    {
        $owner = $this->user(UserRole::Employee);
        $other = $this->user(UserRole::Employee);
        $item = ServiceRequest::factory()->create(['employee_id' => $owner->id]);
        $this->actingAs($other)->getJson("/api/requests/{$item->id}/activity")->assertForbidden();
        $this->actingAs($owner)->getJson("/api/requests/{$item->id}/activity")->assertOk();
    }
}
