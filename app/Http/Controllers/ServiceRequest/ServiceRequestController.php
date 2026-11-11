<?php

namespace App\Http\Controllers\ServiceRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexServiceRequestsRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\RequestActivityResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(IndexServiceRequestsRequest $request, ServiceRequestService $service)
    {
        return ServiceRequestResource::collection($service->listFor($request->user(), $request->validated()))
            ->additional(['success' => true]);
    }

    public function store(StoreServiceRequest $request, ServiceRequestService $service)
    {
        $item = $service->create($request->user(), $request->validated());

        return (new ServiceRequestResource($item))
            ->additional(['success' => true, 'message' => 'Request created.'])
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $service)
    {
        return (new ServiceRequestResource($service->details($request->user(), $serviceRequest)))
            ->additional(['success' => true]);
    }

    public function activity(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $service)
    {
        return RequestActivityResource::collection($service->activity($request->user(), $serviceRequest))
            ->additional(['success' => true]);
    }

    public function update(UpdateServiceRequest $request, ServiceRequest $serviceRequest, ServiceRequestService $service)
    {
        return (new ServiceRequestResource($service->update($request->user(), $serviceRequest, $request->validated())))
            ->additional(['success' => true, 'message' => 'Request updated.']);
    }
}
