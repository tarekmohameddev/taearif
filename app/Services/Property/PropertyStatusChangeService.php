<?php

namespace App\Services\Property;

use App\Domain\CustomersHub\Services\CustomerAssignedPropertyService;
use App\Events\PropertyStatusChanged;
use App\Models\Logs\PropertyLog;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PropertyStatusChangeService
{
    public function __construct(
        private readonly PropertyStatusSyncService $statusSync,
        private readonly CustomerAssignedPropertyService $assignedPropertyService,
    ) {}

    /**
     * @return array{property: Property, customer: ?array}
     */
    public function changeStatus(
        Property $property,
        string $unitStatus,
        ?string $reason = null,
        ?int $customerId = null,
        ?int $actorId = null,
    ): array {
        $oldUnitStatus = $property->unit_status;

        if ($unitStatus === 'reserved' && ! $customerId) {
            throw new UnprocessableEntityHttpException('customer_id is required when unit_status is reserved.');
        }

        DB::transaction(function () use ($property, $unitStatus, $customerId): void {
            $property->unit_status = $unitStatus;
            $this->statusSync->syncModel($property);
            $property->save();

            if ($unitStatus === 'reserved' && $customerId) {
                $ownerUser = User::find($property->user_id);
                $tenantOwnerId = $ownerUser && method_exists($ownerUser, 'tenantOwnerId')
                    ? (int) $ownerUser->tenantOwnerId()
                    : (int) $property->user_id;

                $this->assignedPropertyService->attach($tenantOwnerId, $customerId, $property->id);
            }
        });

        $property->refresh();

        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $property->id,
            'tenant_id' => $ctx['tenant_id'] ?? $property->user_id,
            'action' => 'status_change',
            'reason' => $reason,
            'changes' => [
                'old_status' => $oldUnitStatus,
                'new_status' => $unitStatus,
                'reason' => $reason,
                'customer_id' => $customerId,
            ],
        ]));

        event(new PropertyStatusChanged(
            $property,
            $oldUnitStatus,
            $unitStatus,
            $reason,
            $customerId,
            $actorId,
        ));

        $customer = null;
        if ($customerId) {
            $customerModel = \App\Models\ApiCustomer::find($customerId);
            if ($customerModel) {
                $customer = [
                    'id' => $customerModel->id,
                    'name' => $customerModel->name,
                    'phone' => $customerModel->phone_number ?? null,
                ];
            }
        }

        return [
            'property' => $property,
            'customer' => $customer,
        ];
    }
}
