<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Domain\Services\CustomDomainService;
use App\Http\Requests\Admin\Domain\StoreDomainRequest;
use App\Http\Requests\Admin\Domain\UpdateDomainRequest;
use App\Http\Resources\Admin\CustomDomainResource;
use App\Http\Resources\Admin\CustomDomainCollection;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Domain Controller
 * 
 * Handles custom domain management endpoints
 */
class DomainController extends BaseController
{
    /**
     * @var CustomDomainService
     */
    protected $domainService;

    /**
     * DomainController constructor.
     *
     * @param CustomDomainService $domainService
     */
    public function __construct(CustomDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * Get paginated list of domains
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'user_id',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min($request->input('per_page', 20), 100);
            $domains = $this->domainService->getDomains($filters, $perPage);

            return $this->successResponse(
                new CustomDomainCollection($domains),
                'Domains retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domains.');
        }
    }

    /**
     * Create a new custom domain
     * 
     * @param StoreDomainRequest $request
     * @return JsonResponse
     */
    public function store(StoreDomainRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $domain = $this->domainService->createDomain($data);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain created successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create domain.');
        }
    }

    /**
     * Get domain by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->getDomainById($id);
            $domain->load(['user.generalSettings', 'apiDomainSetting']);

            $apiDomain = $domain->apiDomainSetting;
            $domainName = $apiDomain?->custom_name ?? ($domain->current_domain ?? $domain->requested_domain);
            
            // Determine status
            $legacyStatusValue = $domain->getOriginal('status');
            if ($legacyStatusValue === null) {
                $legacyStatusValue = $domain->status ? 1 : 0;
            }
            $legacyStatusKey = match ((int) $legacyStatusValue) {
                0 => 'pending',
                1 => 'active',
                2 => 'rejected',
                3 => 'removed',
                default => 'inactive',
            };
            $statusKey = $apiDomain && $apiDomain->status
                ? $apiDomain->status
                : ($domain->isApproved() ? 'active' : ($domain->isPending() ? 'pending' : $legacyStatusKey));
            $isActive = $statusKey === 'active';

            // Tenant Information
            $user = $domain->user;
            $fullName = trim((($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
            $siteName = optional($user->generalSettings)->site_name;
            $tenantName = $siteName ?: ($fullName !== '' ? $fullName : ($user->company_name ?? $user->username));

            // SSL Information
            $sslEnabled = $apiDomain?->ssl ?? false;
            $sslStatus = $sslEnabled ? 'valid' : ($apiDomain && $apiDomain->status === 'pending' ? 'provisioning' : 'invalid');
            
            // Get DNS records (stored or fallback to generated)
            $dnsRecords = [];
            if ($apiDomain) {
                // Use stored DNS records if available
                $dnsRecords = $apiDomain->dns_records ?? [];
                
                // If no stored records, fall back to generated ones
                if (empty($dnsRecords)) {
                    $originalUser = auth()->user();
                    if ($user) {
                        auth()->setUser($user);
                    }
                    $dnsRecords = $apiDomain->getDnsRecords();
                    if ($originalUser) {
                        auth()->setUser($originalUser);
                    }
                }
            }

            // Build detailed response matching the UI structure
            $response = [
                'id' => $domain->id,
                'domain' => $domainName,
                
                // SSL Information
                'ssl' => [
                    'status' => $sslStatus,
                    'enabled' => $sslEnabled,
                    'issuer' => 'soon', // TODO: Store in DB if needed
                    'expires_at' => 'soon', // TODO: Store SSL expiry date in DB if needed
                    'ip_address' => 'soon', // TODO: Store IP address in DB if needed
                ],
                
                // Domain Information
                'domain_info' => [
                    'domain' => $domainName,
                    'status' => $statusKey,
                    'status_label' => $isActive ? 'active' : $statusKey,
                    'registrar' => $apiDomain?->registrar,
                    'created_at' => optional($domain->created_at)->toDateString(),
                    'expires_at' => optional($apiDomain?->expires_at)->toDateString(),
                    'auto_renewal' => (bool) ($apiDomain?->auto_renewal ?? false),
                ],
                
                // Tenant Information
                'tenant' => [
                    'id' => $user?->id,
                    'name' => $tenantName,
                    'email' => $user?->email,
                    'username' => $user?->username,
                    'site_name' => $siteName,
                    'company_name' => $user?->company_name,
                    'last_updated' => optional($domain->updated_at)->toDateString(),
                ],
                
                // Name Servers (TODO: Store in DB if needed)
                'name_servers' => [
                    ['name' => 'ns1.example.com', 'label' => 'NS1'],
                    ['name' => 'ns2.example.com', 'label' => 'NS2'],
                ],
                
                // DNS Records
                'dns_records' => $dnsRecords,
                
                // Additional metadata
                'is_primary' => (bool) ($apiDomain?->primary ?? false),
                'added_date' => optional($apiDomain?->added_date ?? $apiDomain?->created_at)->toDateString(),
                'created_at' => optional($domain->created_at)->toIso8601String(),
                'updated_at' => optional($domain->updated_at)->toIso8601String(),
            ];

            return $this->successResponse(
                $response,
                'Domain details retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domain.');
        }
    }

    /**
     * Update existing domain
     * 
     * @param UpdateDomainRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateDomainRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $domain = $this->domainService->updateDomain($id, $data);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update domain.');
        }
    }

    /**
     * Delete domain
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->domainService->deleteDomain($id);

            return $this->successResponse(
                null,
                'Domain deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete domain.');
        }
    }

    /**
     * Approve domain request
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->approveDomain($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain approved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to approve domain.');
        }
    }

    /**
     * Reject domain request
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function reject(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->rejectDomain($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain rejected successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to reject domain.');
        }
    }

    /**
     * Toggle domain status
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->toggleStatus($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain status toggled successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to toggle domain status.');
        }
    }

    /**
     * Get domain statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->domainService->getDomainStatistics();

            return $this->successResponse(
                $stats,
                'Domain statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domain statistics.');
        }
    }

    /**
     * Update registrar / expiry / auto-renewal metadata for a domain.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateMetadata(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'registrar'    => 'nullable|string|max:100',
                'expires_at'   => 'nullable|date',
                'auto_renewal' => 'nullable|boolean',
            ]);

            // Load domain
            $domain = $this->domainService->getDomainById($id);

            // Load or create linked ApiDomainSetting
            $setting = $domain->apiDomainSetting()->first();
            if (!$setting) {
                $setting = \App\Models\Api\ApiDomainSetting::create([
                    'user_id'          => $domain->user_id,
                    'custom_domain_id' => $domain->id,
                    'name'             => optional($domain->user)->getFullNameAttribute() ?: optional($domain->user)->username,
                    'custom_name'      => $domain->current_domain ?? $domain->requested_domain,
                    'status'           => 'pending',
                    'primary'          => false,
                    'ssl'              => false,
                    'added_date'       => now()->toDateString(),
                ]);
            }

            // Persist provided fields
            if (array_key_exists('registrar', $validated)) {
                $setting->registrar = $validated['registrar'];
            }
            if (array_key_exists('expires_at', $validated)) {
                $setting->expires_at = $validated['expires_at'];
            }
            if (array_key_exists('auto_renewal', $validated)) {
                $setting->auto_renewal = (bool) $validated['auto_renewal'];
            }
            $setting->save();

            // Return updated resource
            $domain->load(['user.generalSettings', 'apiDomainSetting']);

            return $this->successResponse(
                new \App\Http\Resources\Admin\CustomDomainResource($domain),
                'Domain metadata updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update domain metadata.');
        }
    }

    /**
     * Get renewal summary for a domain (pricing options, current metadata).
     */
    public function renewalSummary(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->getDomainById($id);
            $domain->load(['user.generalSettings', 'apiDomainSetting']);

            $pricing = app(\App\Domain\Domain\Services\DomainRenewalPricingService::class);
            $options = $pricing->getOptionsForDomain($domain);

            $api = $domain->apiDomainSetting;

            $payload = [
                'domain' => $api?->custom_name ?? ($domain->current_domain ?? $domain->requested_domain),
                'registrar' => $api?->registrar,
                'expires_at' => optional($api?->expires_at)->toDateString(),
                'auto_renewal' => (bool) ($api?->auto_renewal ?? false),
                'currency' => $options['currency'] ?? config('domain-renewal.currency', 'SAR'),
                'options' => $options['options'] ?? $options,
            ];

            return $this->successResponse($payload, 'Renewal summary retrieved successfully');
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve renewal summary.');
        }
    }

    /**
     * Renew a domain by selected period (simple immediate extension).
     */
    public function renew(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'period' => 'required|string',
            ]);

            $domain = $this->domainService->getDomainById($id);
            $domain->load(['user.generalSettings', 'apiDomainSetting']);

            $pricing = app(\App\Domain\Domain\Services\DomainRenewalPricingService::class);
            $option = $pricing->resolveOption($domain, $data['period']);
            if (!$option) {
                return $this->errorResponse('Invalid renewal period', 'VALIDATION_ERROR', 422);
            }

            $years = (int) $option['years'];
            $price = (float) $option['price'];

            // Ensure setting exists
            $api = $domain->apiDomainSetting;
            if (!$api) {
                $api = \App\Models\Api\ApiDomainSetting::create([
                    'user_id'          => $domain->user_id,
                    'custom_domain_id' => $domain->id,
                    'name'             => optional($domain->user)->getFullNameAttribute() ?: optional($domain->user)->username,
                    'custom_name'      => $domain->current_domain ?? $domain->requested_domain,
                    'status'           => 'active',
                    'primary'          => false,
                    'ssl'              => (bool) $domain->status,
                    'added_date'       => now()->toDateString(),
                ]);
            }

            // Extend expiry: max(current_expires_at, today) + years
            $base = $api->expires_at ? \Carbon\Carbon::parse($api->expires_at) : null;
            $start = $base && $base->greaterThan(now()) ? $base : now();
            $old = $api->expires_at ? \Carbon\Carbon::parse($api->expires_at)->toDateString() : null;
            $newExpires = $start->copy()->addYears($years)->startOfDay();

            $api->expires_at = $newExpires->toDateString();
            $api->save();

            $domain->load(['apiDomainSetting', 'user.generalSettings']);

            return $this->successResponse(
                new \App\Http\Resources\Admin\CustomDomainResource($domain),
                'Domain renewed successfully',
                200,
                [
                    'renewal' => [
                        'period' => $option['period'],
                        'years' => $years,
                        'price' => $price,
                        'currency' => $option['currency'] ?? config('domain-renewal.currency', 'SAR'),
                        'old_expires_at' => $old,
                        'new_expires_at' => $newExpires->toDateString(),
                    ],
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to renew domain.');
        }
    }

    /**
     * Enable/disable SSL for a domain.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateSsl(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'ssl' => 'required|boolean',
            ]);

            $domain = $this->domainService->getDomainById($id);
            $domain->load('apiDomainSetting');

            $setting = $domain->apiDomainSetting;
            if (!$setting) {
                $setting = \App\Models\Api\ApiDomainSetting::create([
                    'user_id'          => $domain->user_id,
                    'custom_domain_id' => $domain->id,
                    'name'             => optional($domain->user)->getFullNameAttribute() ?: optional($domain->user)->username,
                    'custom_name'      => $domain->current_domain ?? $domain->requested_domain,
                    'status'           => $domain->isApproved() ? 'active' : 'pending',
                    'primary'          => false,
                    'ssl'              => false,
                    'added_date'       => now()->toDateString(),
                ]);
            }

            $setting->ssl = (bool) $data['ssl'];
            $setting->save();

            $domain->load(['apiDomainSetting', 'user.generalSettings']);

            return $this->successResponse(
                new \App\Http\Resources\Admin\CustomDomainResource($domain),
                'SSL status updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update SSL status.');
        }
    }

    /**
     * Get DNS records for a domain.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getDnsRecords(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->getDomainById($id);
            $domain->load('apiDomainSetting');

            $apiDomain = $domain->apiDomainSetting;
            
            // Get stored DNS records or use default generated ones
            $dnsRecords = $apiDomain?->dns_records ?? [];
            
            // If no stored records, fall back to generated ones
            if (empty($dnsRecords) && $apiDomain) {
                $user = $domain->user;
                $originalUser = auth()->user();
                if ($user) {
                    auth()->setUser($user);
                }
                $dnsRecords = $apiDomain->getDnsRecords();
                if ($originalUser) {
                    auth()->setUser($originalUser);
                }
            }

            return $this->successResponse(
                ['dns_records' => $dnsRecords],
                'DNS records retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve DNS records.');
        }
    }

    /**
     * Update DNS records for a domain.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateDnsRecords(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'dns_records' => 'required|array',
                'dns_records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,PTR',
                'dns_records.*.name' => 'required|string|max:255',
                'dns_records.*.value' => 'required|string|max:65535',
                'dns_records.*.ttl' => 'required|integer|min:60|max:86400',
            ]);

            $domain = $this->domainService->getDomainById($id);
            $domain->load('apiDomainSetting');

            $setting = $domain->apiDomainSetting;
            if (!$setting) {
                $setting = \App\Models\Api\ApiDomainSetting::create([
                    'user_id'          => $domain->user_id,
                    'custom_domain_id' => $domain->id,
                    'name'             => optional($domain->user)->getFullNameAttribute() ?: optional($domain->user)->username,
                    'custom_name'      => $domain->current_domain ?? $domain->requested_domain,
                    'status'           => $domain->isApproved() ? 'active' : 'pending',
                    'primary'          => false,
                    'ssl'              => false,
                    'added_date'       => now()->toDateString(),
                ]);
            }

            $setting->dns_records = $data['dns_records'];
            $setting->save();

            $domain->load('apiDomainSetting');

            return $this->successResponse(
                [
                    'dns_records' => $setting->dns_records,
                ],
                'DNS records updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update DNS records.');
        }
    }

    /**
     * Centralized error handling for domain endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
                ['error_code' => $e->getErrorCode()]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'DOMAIN_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

