<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRequest;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflow;
use Illuminate\Http\Request;

class RequestWorkflowController extends Controller
{
    public function submit(Request $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $item = $workflow->transition($serviceRequest, $request->user(), 'submit');

        return (new ServiceRequestResource($item))
            ->additional(['success' => true, 'message' => 'Request submitted for review.']);
    }

    public function decision(DecisionRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $data = $request->validated();
        $item = $workflow->transition($serviceRequest, $request->user(), $data['decision'], $data['comment']);

        return (new ServiceRequestResource($item))
            ->additional(['success' => true, 'message' => 'Manager decision recorded.']);
    }

    public function assign(AssignRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $data = $request->validated();
        $item = $workflow->transition($serviceRequest, $request->user(), 'assign', $data['comment'] ?? null, $data['assigned_to']);

        return (new ServiceRequestResource($item))
            ->additional(['success' => true, 'message' => 'Request assigned.']);
    }

    public function status(StatusRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $data = $request->validated();
        $action = $data['status'] === 'in_progress' ? 'start' : 'complete';
        $item = $workflow->transition($serviceRequest, $request->user(), $action, $data['comment'] ?? null);

        return (new ServiceRequestResource($item))
            ->additional(['success' => true, 'message' => 'Request status updated.']);
    }
}
