<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\Rms\Maintenance\StoreMaintenanceRequest;
use App\Http\Requests\Rms\Maintenance\UpdateMaintenanceRequest;
use App\Http\Requests\Api\V1\Rms\UpdateMaintenanceStatusRequest;
use Illuminate\Http\Request;
use App\Services\Rms\MaintenanceService;

class MaintenanceController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function index(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $filters = $request->only(['status', 'priority', 'category', 'rental_id', 'from', 'to']);
            $results = $this->maintenanceService->list($this->getUserId(), $filters);

            return $this->success($results);
        }, 'list maintenance tickets');
    }

    public function store(StoreMaintenanceRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $ticket = $this->maintenanceService->create(
                $request->validated(),
                $this->getUserId()
            );

            return $this->created($ticket, 'Maintenance ticket created successfully');
        }, 'create maintenance ticket');
    }

    public function show($id)
    {
        return $this->executeWithExceptionHandling(function () use ($id) {
            $ticket = $this->maintenanceService->find($this->getUserId(), $id);
            return $this->success($ticket);
        }, 'retrieve maintenance ticket');
    }

    public function update(UpdateMaintenanceRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $ticket = $this->maintenanceService->update(
                $id,
                $request->validated(),
                $this->getUserId()
            );

            return $this->success($ticket, 'Maintenance ticket updated successfully');
        }, 'update maintenance ticket');
    }

    public function updateStatus(UpdateMaintenanceStatusRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $validated = $request->validated();
            $ticket = $this->maintenanceService->changeStatus($id, $validated['status'], $this->getUserId());

            return $this->success($ticket, 'Maintenance ticket status updated successfully');
        }, 'update maintenance ticket status');
    }

    public function destroy($id)
    {
        return $this->executeWithExceptionHandling(function () use ($id) {
            $this->maintenanceService->delete($id, $this->getUserId());
            return $this->noContent();
        }, 'delete maintenance ticket');
    }
}
