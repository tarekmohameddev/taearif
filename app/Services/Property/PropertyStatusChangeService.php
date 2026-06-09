<?php

namespace App\Services\Property;

use App\Domain\CustomersHub\Services\CustomerAssignedPropertyService;
use App\Events\PropertyStatusChanged;
use App\Models\Logs\PropertyLog;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Services\Audit\EntityAuditLogger;
use App\Support\AuditContext;
use App\Support\PropertyAuditFields;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PropertyStatusChangeService
{
    public function __construct(
        private readonly PropertyStatusSyncService $statusSync,
        private readonly CustomerAssignedPropertyService $assignedPropertyService,
        private readonly PropertyCrmDealCloseService $crmDealCloseService,
        private readonly EntityAuditLogger $entityAuditLogger,
    ) {}

    /**
     * @return array{property: Property, customer: ?array, crm: ?array}
     */
    public function changeStatus(
        Property $property,
        string $unitStatus,
        ?string $reason = null,
        ?int $customerId = null,
        ?int $actorId = null,
    ): array {
        $oldUnitStatus = $property->unit_status;
        $beforeStatus = $this->extractStatusFields($property->getAttributes());

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

        $crmResult = null;
        if ($unitStatus === 'sold') {
            $crmResult = $this->crmDealCloseService->closeDealsForSoldProperty(
                $property,
                $customerId,
                $actorId,
            );
        }

        $logChanges = [
            'old_status' => $oldUnitStatus,
            'new_status' => $unitStatus,
            'reason' => $reason,
            'customer_id' => $customerId,
        ];
        if ($crmResult !== null) {
            $logChanges['crm_close'] = [
                'closed_requests' => $crmResult['closed_requests'],
                'closed_customers' => $crmResult['closed_customers'],
                'success' => $crmResult['success'],
                'warnings' => $crmResult['warnings'],
            ];
        }

        $ctx = AuditContext::data();
        $tenantId = $ctx['tenant_id'] ?? $property->user_id;

        PropertyLog::create(array_merge($ctx, [
            'property_id' => $property->id,
            'tenant_id' => $tenantId,
            'action' => 'status_change',
            'reason' => $reason,
            'changes' => $logChanges,
        ]));

        $afterStatus = $this->extractStatusFields($property->getAttributes());
        $this->entityAuditLogger->logFields(
            'property',
            $property->id,
            $beforeStatus,
            $afterStatus,
            PropertyAuditFields::STATUS,
            'status_change',
            $reason,
            $tenantId,
        );

        if ($customerId) {
            $this->entityAuditLogger->logField(
                'property',
                $property->id,
                'customer_id',
                null,
                $customerId,
                'status_change',
                $reason,
                $tenantId,
            );
        }

        if ($crmResult !== null) {
            $this->entityAuditLogger->logField(
                'property',
                $property->id,
                'crm_close',
                null,
                $logChanges['crm_close'],
                'status_change',
                $reason,
                $tenantId,
            );
        }

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
            'crm' => $crmResult,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function extractStatusFields(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip(PropertyAuditFields::STATUS));
    }
}
