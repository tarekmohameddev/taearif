<?php

namespace App\Services\Property;

use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Models\Api\Crm\CrmRequest;
use App\Models\Api\UserApiCustomerStage;
use App\Models\ApiCustomer;
use App\Models\Property\PropertyCrmRelation;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Services\CrmCustomerStageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PropertyCrmDealCloseService
{
  private const CLOSED_UPR_STAGES = ['deal_completed', 'closing'];

  public function __construct(
    private readonly ActionsAggregatorService $actionsAggregator,
    private readonly CrmCustomerStageService $customerStageService,
  ) {}

  /**
   * @return array{
   *   success: bool,
   *   closed_requests: int,
   *   closed_customers: int,
   *   warnings: string[],
   *   errors: string[]
   * }
   */
  public function closeDealsForSoldProperty(
    Property $property,
    ?int $customerId = null,
    ?int $actorId = null,
  ): array {
    $result = [
      'success' => true,
      'closed_requests' => 0,
      'closed_customers' => 0,
      'warnings' => [],
      'errors' => [],
    ];

    if (! $this->isSaleListing($property)) {
      return $result;
    }

    $tenantOwnerId = $this->resolveTenantOwnerId($property);
    if ($tenantOwnerId <= 0) {
      $result['success'] = false;
      $result['errors'][] = 'Could not resolve tenant owner for property.';

      return $result;
    }

    $closedCustomerStage = $this->resolveClosedCustomerStage($tenantOwnerId);
    if ($closedCustomerStage === null && Schema::hasTable('crm_requests')) {
      $hasLinkedCrmRequests = CrmRequest::query()
        ->where('user_id', $tenantOwnerId)
        ->where('property_id', $property->id)
        ->exists();

      if ($hasLinkedCrmRequests) {
        $result['success'] = false;
        $result['errors'][] = 'Could not resolve closed stage for tenant.';
      }
    }

    $closedRequestIds = [];
    $closedCustomerIds = [];

    $closedRequestIds = array_merge(
      $closedRequestIds,
      $this->closeCrmRequestsByProperty($tenantOwnerId, (int) $property->id, $closedCustomerStage, $result)
    );

    $closedRequestIds = array_merge(
      $closedRequestIds,
      $this->closePropertyRequestsForProperty($tenantOwnerId, (int) $property->id, $result)
    );

    $closedRequestIds = array_merge(
      $closedRequestIds,
      $this->closeCrmRequestsFromRelations($tenantOwnerId, (int) $property->id, $closedCustomerStage, $result)
    );

    $result['closed_requests'] = count(array_unique($closedRequestIds));

    $closedCustomerIds = array_merge(
      $closedCustomerIds,
      $this->closeCustomersAssignedToProperty($tenantOwnerId, (int) $property->id, $closedCustomerStage, $result)
    );

    if ($customerId) {
      $closedCustomerIds = array_merge(
        $closedCustomerIds,
        $this->closeCustomerById($tenantOwnerId, $customerId, $closedCustomerStage, $result)
      );
    }

    $result['closed_customers'] = count(array_unique(array_filter($closedCustomerIds)));
    $result['success'] = empty($result['errors']);

    return $result;
  }

  private function isSaleListing(Property $property): bool
  {
    $purpose = $property->listing_purpose ?? $property->purpose ?? 'sale';

    return in_array($purpose, ['sale', 'sold'], true);
  }

  private function resolveTenantOwnerId(Property $property): int
  {
    $ownerUser = User::find($property->user_id);
    if ($ownerUser && method_exists($ownerUser, 'tenantOwnerId')) {
      return (int) $ownerUser->tenantOwnerId();
    }

    return (int) $property->user_id;
  }

  private function resolveClosedCustomerStage(int $tenantOwnerId): ?UserApiCustomerStage
  {
    if (! Schema::hasTable('users_api_customers_stages')) {
      return null;
    }

    $stage = UserApiCustomerStage::query()
      ->where('user_id', $tenantOwnerId)
      ->where('is_active', true)
      ->where(function ($q) {
        $q->where('stage_name', 'LIKE', '%closing%')
          ->orWhere('stage_name', 'LIKE', '%post_sale%')
          ->orWhere('stage_name', 'LIKE', '%اقفال%');
      })
      ->orderByDesc('order')
      ->first();

    if ($stage) {
      return $stage;
    }

    return UserApiCustomerStage::query()
      ->where('user_id', $tenantOwnerId)
      ->where('is_active', true)
      ->orderByDesc('order')
      ->first();
  }

  /**
   * @param  array{success: bool, closed_requests: int, closed_customers: int, warnings: string[], errors: string[]}  $result
   * @return list<int>
   */
  private function closeCrmRequestsByProperty(
    int $tenantOwnerId,
    int $propertyId,
    ?UserApiCustomerStage $closedStage,
    array &$result,
  ): array {
    if (! Schema::hasTable('crm_requests') || $closedStage === null) {
      return [];
    }

    $closed = [];
    $requests = CrmRequest::query()
      ->where('user_id', $tenantOwnerId)
      ->where('property_id', $propertyId)
      ->get(['id', 'stage_id']);

    foreach ($requests as $request) {
      if ((int) $request->stage_id === (int) $closedStage->id) {
        continue;
      }

      try {
        $updated = CrmRequest::query()
          ->where('id', $request->id)
          ->where('user_id', $tenantOwnerId)
          ->update([
            'stage_id' => $closedStage->id,
            'updated_at' => now(),
          ]);

        if ($updated > 0) {
          $closed[] = (int) $request->id;
        }
      } catch (\Throwable $e) {
        $result['warnings'][] = "Failed to close CRM request {$request->id}: {$e->getMessage()}";
        Log::warning('Failed to close CRM request on property sold', [
          'request_id' => $request->id,
          'property_id' => $propertyId,
          'error' => $e->getMessage(),
        ]);
      }
    }

    return $closed;
  }

  /**
   * @param  array{success: bool, closed_requests: int, closed_customers: int, warnings: string[], errors: string[]}  $result
   * @return list<int>
   */
  private function closePropertyRequestsForProperty(int $tenantOwnerId, int $propertyId, array &$result): array
  {
    if (! Schema::hasTable('users_property_requests')) {
      return [];
    }

    $query = DB::table('users_property_requests')
      ->where('user_id', $tenantOwnerId)
      ->where(function ($q) use ($propertyId) {
        $q->where('initial_property_id', $propertyId);
        if (Schema::hasColumn('users_property_requests', 'property_ids')) {
          $q->orWhereJsonContains('property_ids', $propertyId);
        }
      });

    if (Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
      $query->whereNotIn('customers_hub_stage_id', self::CLOSED_UPR_STAGES);
    }

    $closed = [];

    foreach ($query->pluck('id') as $requestId) {
      try {
        if ($this->actionsAggregator->completeAction($tenantOwnerId, "property_request_{$requestId}")) {
          $closed[] = (int) $requestId;
        }
      } catch (\Throwable $e) {
        $result['warnings'][] = "Failed to close property request {$requestId}: {$e->getMessage()}";
        Log::warning('Failed to close property request on property sold', [
          'request_id' => $requestId,
          'property_id' => $propertyId,
          'error' => $e->getMessage(),
        ]);
      }
    }

    return $closed;
  }

  /**
   * @param  array{success: bool, closed_requests: int, closed_customers: int, warnings: string[], errors: string[]}  $result
   * @return list<int>
   */
  private function closeCrmRequestsFromRelations(
    int $tenantOwnerId,
    int $propertyId,
    ?UserApiCustomerStage $closedStage,
    array &$result,
  ): array {
    if (! Schema::hasTable('property_crm_relations') || ! Schema::hasTable('crm_requests') || $closedStage === null) {
      return [];
    }

    $requestIds = PropertyCrmRelation::query()
      ->where('property_id', $propertyId)
      ->distinct()
      ->pluck('request_id');

    $closed = [];

    foreach ($requestIds as $requestId) {
      $request = CrmRequest::query()
        ->where('id', $requestId)
        ->where('user_id', $tenantOwnerId)
        ->first(['id', 'stage_id']);

      if (! $request || (int) $request->stage_id === (int) $closedStage->id) {
        continue;
      }

      try {
        $updated = CrmRequest::query()
          ->where('id', $request->id)
          ->where('user_id', $tenantOwnerId)
          ->update([
            'stage_id' => $closedStage->id,
            'updated_at' => now(),
          ]);

        if ($updated > 0) {
          $closed[] = (int) $request->id;
        }
      } catch (\Throwable $e) {
        $result['warnings'][] = "Failed to close linked CRM request {$request->id}: {$e->getMessage()}";
      }
    }

    return $closed;
  }

  /**
   * @param  array{success: bool, closed_requests: int, closed_customers: int, warnings: string[], errors: string[]}  $result
   * @return list<int>
   */
  private function closeCustomersAssignedToProperty(
    int $tenantOwnerId,
    int $propertyId,
    ?UserApiCustomerStage $closedStage,
    array &$result,
  ): array {
    if (! Schema::hasTable('api_customer_assigned_property') || $closedStage === null) {
      return [];
    }

    $customerIds = DB::table('api_customer_assigned_property')
      ->where('property_id', $propertyId)
      ->pluck('customer_id');

    $closed = [];

    foreach ($customerIds as $id) {
      $closed = array_merge(
        $closed,
        $this->closeCustomerById($tenantOwnerId, (int) $id, $closedStage, $result)
      );
    }

    return $closed;
  }

  /**
   * @param  array{success: bool, closed_requests: int, closed_customers: int, warnings: string[], errors: string[]}  $result
   * @return list<int>
   */
  private function closeCustomerById(
    int $tenantOwnerId,
    int $customerId,
    ?UserApiCustomerStage $closedStage,
    array &$result,
  ): array {
    if ($closedStage === null || ! Schema::hasTable('api_customers')) {
      return [];
    }

    $customer = ApiCustomer::query()
      ->where('id', $customerId)
      ->where('user_id', $tenantOwnerId)
      ->first();

    if (! $customer) {
      return [];
    }

    if ((int) $customer->stage_id === (int) $closedStage->id) {
      return [];
    }

    try {
      $this->customerStageService->changeStage($customer, $closedStage);

      return [$customerId];
    } catch (\Throwable $e) {
      $result['warnings'][] = "Failed to move customer {$customerId} to closed stage: {$e->getMessage()}";
      Log::warning('Failed to move customer to closed stage on property sold', [
        'customer_id' => $customerId,
        'property_id_context' => true,
        'error' => $e->getMessage(),
      ]);

      return [];
    }
  }
}
