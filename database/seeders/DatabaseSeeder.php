<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts only. Do not run this seeder in a public production environment.
        DB::transaction(function (): void {
            $manager = User::create([
                'name' => 'Maya Manager', 'email' => 'manager@example.com',
                'password' => 'Password123!', 'role' => UserRole::Manager, 'department' => 'IT',
            ]);
            $operator = User::create([
                'name' => 'Oliver Operations', 'email' => 'operations@example.com',
                'password' => 'Password123!', 'role' => UserRole::Operations, 'department' => 'Operations',
            ]);
            $employees = [];
            foreach ([['Alex Employee', 'alex@example.com'], ['Sam Employee', 'sam@example.com']] as [$name, $email]) {
                $employees[] = User::create([
                    'name' => $name, 'email' => $email, 'password' => 'Password123!',
                    'role' => UserRole::Employee, 'manager_id' => $manager->id, 'department' => 'IT',
                ]);
            }

            $statuses = [
                RequestStatus::Draft, RequestStatus::Pending, RequestStatus::Returned,
                RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Assigned,
                RequestStatus::InProgress, RequestStatus::Completed, RequestStatus::Pending,
                RequestStatus::Approved,
            ];
            $paths = [
                'draft' => [], 'pending' => ['submit'], 'returned' => ['submit', 'return'],
                'approved' => ['submit', 'approve'], 'rejected' => ['submit', 'reject'],
                'assigned' => ['submit', 'approve', 'assign'],
                'in_progress' => ['submit', 'approve', 'assign', 'start'],
                'completed' => ['submit', 'approve', 'assign', 'start', 'complete'],
            ];
            $targets = [
                'submit' => RequestStatus::Pending, 'return' => RequestStatus::Returned,
                'approve' => RequestStatus::Approved, 'reject' => RequestStatus::Rejected,
                'assign' => RequestStatus::Assigned, 'start' => RequestStatus::InProgress,
                'complete' => RequestStatus::Completed,
            ];
            $audit = app(RequestActivityLogger::class);

            foreach ($statuses as $i => $finalStatus) {
                $employee = $employees[$i % count($employees)];
                $item = ServiceRequest::create([
                    'employee_id' => $employee->id,
                    'title' => 'Sample service request '.($i + 1),
                    'description' => 'Seeded example request for testing the API workflow.',
                    'category' => ['it_support', 'equipment', 'maintenance', 'access', 'other'][$i % 5],
                    'priority' => ['low', 'medium', 'high'][$i % 3],
                    'status' => RequestStatus::Draft,
                ]);
                $audit->log($item, $employee, 'created', null, RequestStatus::Draft);

                // Seed realistic history without dispatching demo notifications.
                foreach ($paths[$finalStatus->value] as $action) {
                    $actor = match ($action) {
                        'submit' => $employee,
                        'approve', 'reject', 'return' => $manager,
                        default => $operator,
                    };
                    $comment = match ($action) {
                        'approve' => 'Approved for processing.',
                        'reject' => 'Request declined.',
                        'return' => 'Please provide further details.',
                        default => null,
                    };
                    $from = $item->status;
                    $item->status = $targets[$action];
                    if ($action === 'assign') {
                        $item->assigned_to = $operator->id;
                    }
                    $item->save();
                    $audit->log($item, $actor, $action, $from, $item->status, $comment);
                    if ($comment !== null) {
                        $item->comments()->create(['user_id' => $actor->id, 'body' => $comment]);
                    }
                }
            }
        });
    }
}
