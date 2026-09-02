<?php

namespace App\Http\Controllers\Admin;

use Session;
use Validator;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Exceptions\BusinessLogicException;
use App\Domain\Domain\Models\CustomDomain;
use App\Support\DomainHealthMessages;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiDomainSetting;
use App\Models\User\UserCustomDomain;
use App\Services\Vercel\DomainProvisioningService;
use App\Services\Vercel\DomainReconciliationService;
use App\Services\Vercel\DomainStatusSyncService;
use App\Services\Vercel\VercelDomainCache;
use App\Services\Vercel\VercelBackedDomainGuard;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use App\Services\Vercel\VercelDomainInventoryService;
use App\Services\Vercel\VercelMutationGuard;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomDomainController extends Controller
{
    private const HEALTH_FILTERS = [        'issues',
        'linked',
        'unchecked',
        'ns_not_pointing',
        'ns_mismatch',
        'not_on_vercel',
        'unverified',
        'zone_disabled',
        'certificate_pending',
        'certificate_error',
        'expired',
        'provider_error',
        'checks_disabled',
        'ownership_required',
        'dns_misconfigured',
        'apex_only',
    ];

    private const INVENTORY_TTL_SECONDS = 300;

    private const BULK_REPAIR_MAX = 20;
    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly VercelDomainCache $domainCache,
        private readonly VercelDomainInventoryService $inventory,
        private readonly VercelMutationGuard $mutationGuard,
        private readonly DomainReconciliationService $reconciliationService,
        private readonly DomainProvisioningService $provisioningService,
        private readonly DomainStatusSyncService $domainSyncService,
        private readonly VercelBackedDomainGuard $vercelBackedGuard,
    ) {
    }

    public function texts()
    {
        $data['abe'] = BasicExtended::select('domain_request_success_message', 'cname_record_section_title', 'cname_record_section_text')->first();
        return view('admin.domains.custom-texts', $data);
    }

    public function updateTexts(Request $request)
    {
        $rules = [
            'success_message' => 'required|max:255',
            'cname_record_section_title' => 'required|max:255',
            'cname_record_section_text' => 'required'
        ];
        $request->validate($rules);

        $be = BasicExtended::first();
        $be->domain_request_success_message = clean($request->success_message);
        $be->cname_record_section_title = $request->cname_record_section_title;
        $be->cname_record_section_text = clean($request->cname_record_section_text);
        $be->save();

        $request->session()->flash('success', __('domain_admin.texts_updated'));
        return back();
    }

    public function index(Request $request)
    {
        $baseQuery = ApiDomainSetting::query()
            ->when($request->domain, function ($query) use ($request) {
                $query->where('custom_name', 'LIKE', '%' . $request->domain . '%');
            })
            ->when($request->username, function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('username', $request->username);
                });
            });

        if (empty($request->type)) {
            // no status filter
        } elseif ($request->type === 'pending') {
            $baseQuery->where('status', 'pending');
        } elseif ($request->type === 'connected') {
            $baseQuery->where('status', 'active');
        } elseif (in_array($request->type, ['failed', 'rejected'], true)) {
            $baseQuery->whereIn('status', ['failed', 'rejected']);
        } else {
            return view('errors.404');
        }

        $healthFilter = $this->normalizeHealthFilter($request->input('health'));
        if ($request->filled('health') && $healthFilter === null) {
            return view('errors.404');
        }

        $inventorySnapshot = $this->domainCache->cached();
        $vercelNames = $inventorySnapshot['names'] ?? null;
        $inventoryUnreliable = $this->isInventoryUnreliable($inventorySnapshot);

        if ($healthFilter !== null) {
            $matchingIds = $this->filterDomainIdsByHealth(
                (clone $baseQuery)->select(['id', 'custom_name', 'dns_records', 'expires_at'])->get(),
                $healthFilter,
                $vercelNames
            );

            $rcDomains = ApiDomainSetting::with('user')
                ->whereIn('id', $matchingIds)
                ->orderBy('id', 'DESC')
                ->paginate(10);
        } else {
            $rcDomains = (clone $baseQuery)
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(10);
        }

        $capacity = $inventorySnapshot !== null ? $this->buildCapacity($inventorySnapshot) : null;
        $wwwStatesByDomainId = $this->resolveWwwStatesForRows(
            $rcDomains->getCollection(),
            $inventorySnapshot,
            $capacity,
            $inventoryUnreliable
        );

        if ($vercelNames !== null) {
            $rcDomains->getCollection()->transform(function (ApiDomainSetting $domain) use ($vercelNames) {
                $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

                return $domain->setVercelAttachedHint(in_array($apex, $vercelNames, true));
            });
        }

        $data['rcDomains'] = $rcDomains;
        $data['vercelCapacity'] = $capacity;
        $data['domainHealthCounts'] = $this->resolveDomainHealthCounts($inventorySnapshot);
        $data['healthFilter'] = $healthFilter;
        $data['reconciliationSummary'] = $this->buildReconciliationSummary($inventorySnapshot);
        $data['inventoryUnreliable'] = $inventoryUnreliable;
        $data['capacityBlocked'] = $inventoryUnreliable;
        $data['wwwStatesByDomainId'] = $wwwStatesByDomainId;
        $data['repairActionsByDomainId'] = $this->resolveRepairActionsForRows(
            $rcDomains->getCollection(),
            $inventorySnapshot,
            $capacity
        );
        $data['nonProductionSharedProject'] = $this->mutationGuard->isNonProductionSharedProject();

        return view('admin.domains.custom', $data);
    }

    public function diagnostics(Request $request, int $id)
    {
        $domain = ApiDomainSetting::with('user')->findOrFail($id);
        $payload = $this->buildDomainDiagnostics($domain);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('admin.domains.partials.diagnostics-drawer', [
            'domain' => $domain,
            'diagnostics' => $payload,
        ]);
    }

    public function repairVerify(Request $request)
    {
        $baseRules = [
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
        ];

        $domain = ApiDomainSetting::findOrFail((int) $request->input('domain_id'));
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);
        $inventorySnapshot = $this->domainCache->cached();
        $capacity = $inventorySnapshot !== null
            ? $this->inventory->evaluateCapacityForApex($inventorySnapshot, $apex)
            : ['allowed' => false, 'reason' => 'inventory_lower_bound'];

        if (! $capacity['allowed'] && ($capacity['reason'] ?? null) === 'capacity_reached') {
            $baseRules['confirm_domain'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($baseRules);

        if (isset($validated['confirm_domain'])
            && $this->vercel->normalizeApex($validated['confirm_domain']) !== $apex) {
            $request->session()->flash(
                'error',
                __('domain_mutation.confirmation_required', ['domain' => $apex])
            );

            return back();
        }

        $before = $this->domainActivitySnapshot($domain);

        try {
            $provisionResult = $this->provisioningService->run(
                $apex,
                DomainProvisioningService::MODE_ADMIN_REPAIR
            );
        } catch (LockTimeoutException $exception) {
            $request->session()->flash('error', __('domain_mutation.lock_timeout'));

            return back();
        }

        $this->domainSyncService->applyProvisioningResult(
            $domain,
            $provisionResult,
            $request,
            applyFailureThreshold: false
        );
        $domain->refresh();
        $this->domainCache->invalidateAdminCaches();

        \App\Support\TenantActivity::emit(
            $request,
            'domain.repair_verify',
            'api_domains_settings',
            $domain->id,
            $before,
            $this->domainActivitySnapshot($domain)
        );

        $message = DomainHealthMessages::translate((string) ($provisionResult['message'] ?? ''));
        $outcome = (string) ($provisionResult['outcome'] ?? 'pending');

        if ($outcome === 'active') {
            $request->session()->flash('success', $message !== '' ? $message : __('domain_health.repair_active'));
        } elseif ($outcome === 'failed') {
            $request->session()->flash('error', $message !== '' ? $message : __('domain_health.repair_failed'));
        } else {
            $request->session()->flash('success', $message !== '' ? $message : __('domain_health.repair_pending'));
        }

        return back();
    }

    public function claimOwnership(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
        ]);

        $domain = ApiDomainSetting::findOrFail($validated['domain_id']);
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

        try {
            $this->mutationGuard->assertCanMutate($request);
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }

        $before = $this->domainActivitySnapshot($domain);

        try {
            $provisionResult = $this->provisioningService->run(
                $apex,
                DomainProvisioningService::MODE_CLAIM_OWNERSHIP
            );
        } catch (LockTimeoutException $exception) {
            $request->session()->flash('error', __('domain_mutation.lock_timeout'));

            return back();
        }

        $this->domainSyncService->applyProvisioningResult(
            $domain,
            $provisionResult,
            $request,
            applyFailureThreshold: false
        );
        $domain->refresh();
        $this->domainCache->invalidateAdminCaches();

        \App\Support\TenantActivity::emit(
            $request,
            'domain.claim_ownership',
            'api_domains_settings',
            $domain->id,
            $before,
            $this->domainActivitySnapshot($domain)
        );

        $message = DomainHealthMessages::translate((string) ($provisionResult['message'] ?? ''));
        $outcome = (string) ($provisionResult['outcome'] ?? 'pending');

        if ($outcome === 'active') {
            $request->session()->flash('success', $message !== '' ? $message : __('domain_admin.claim_ownership_active'));
        } elseif ($outcome === 'failed') {
            $request->session()->flash('error', $message !== '' ? $message : __('domain_admin.claim_ownership_failed'));
        } else {
            $request->session()->flash('success', $message !== '' ? $message : __('domain_admin.claim_ownership_pending'));
        }

        return back();
    }

    public function bulkRepairVerify(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . self::BULK_REPAIR_MAX],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:api_domains_settings,id'],
        ]);

        try {
            $this->mutationGuard->assertCanMutate($request);
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }

        $domains = ApiDomainSetting::whereIn('id', $validated['ids'])->get();
        $outcomes = [];

        foreach ($domains as $domain) {
            $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

            try {
                $provisionResult = $this->provisioningService->run(
                    $apex,
                    DomainProvisioningService::MODE_ADMIN_REPAIR
                );
                $this->domainSyncService->applyProvisioningResult(
                    $domain,
                    $provisionResult,
                    $request,
                    applyFailureThreshold: false
                );

                $outcomes[] = [
                    'domain' => $apex,
                    'outcome' => (string) ($provisionResult['outcome'] ?? 'pending'),
                    'message' => DomainHealthMessages::translate((string) ($provisionResult['message'] ?? '')),
                ];
            } catch (LockTimeoutException) {
                $outcomes[] = [
                    'domain' => $apex,
                    'outcome' => 'error',
                    'message' => __('domain_mutation.lock_timeout'),
                ];
            } catch (\Throwable $exception) {
                Log::error('Admin bulk repair-verify failed for domain', [
                    'domain_id' => $domain->id,
                    'custom_name' => $domain->custom_name,
                    'error' => $exception->getMessage(),
                ]);

                $outcomes[] = [
                    'domain' => $apex,
                    'outcome' => 'error',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $this->domainCache->invalidateAdminCaches();

        $active = collect($outcomes)->where('outcome', 'active')->count();
        $pending = collect($outcomes)->where('outcome', 'pending')->count();
        $failed = collect($outcomes)->where('outcome', 'failed')->count();
        $errors = collect($outcomes)->where('outcome', 'error')->count();

        $request->session()->flash(
            'success',
            __('domain_admin.bulk_repair_summary', [
                'total' => count($outcomes),
                'active' => $active,
                'pending' => $pending,
                'failed' => $failed,
                'errors' => $errors,
            ])
        );

        if ($failed > 0 || $errors > 0) {
            $detailLines = collect($outcomes)
                ->filter(fn (array $row) => in_array($row['outcome'], ['failed', 'error'], true))
                ->map(fn (array $row) => $row['domain'] . ': ' . $row['message'])
                ->take(5)
                ->implode('; ');

            if ($detailLines !== '') {
                $request->session()->flash('error', $detailLines);
            }
        }

        return back();
    }

    public function refreshInventory(Request $request)
    {
        try {
            $this->mutationGuard->assertCanMutate($request);
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }

        if (! $this->vercel->isConfigured()) {
            $request->session()->flash('error', __('domain_mutation.not_configured'));

            return back();
        }

        try {
            $this->domainCache->invalidateAdminCaches();
            $snapshot = $this->domainCache->fresh();
            $fetchedAt = $snapshot['fetched_at'] ?? now()->toIso8601String();

            $request->session()->flash(
                'success',
                __('domain_admin.inventory_refreshed', ['time' => $fetchedAt])
            );
        } catch (\Throwable $exception) {
            Log::warning('Admin manual Vercel inventory refresh failed', [
                'error' => $exception->getMessage(),
            ]);

            $request->session()->flash('error', __('domain_admin.inventory_refresh_failed'));
        }

        return back();
    }

    public function adoptLegacyOrphan(Request $request)
    {
        $validated = $request->validate([
            'legacy_id' => ['required', 'integer', 'exists:user_custom_domains,id'],
        ]);

        try {
            $legacy = $this->resolveLegacyOrphanRow((int) $validated['legacy_id']);
            $apex = $this->resolveLegacyOrphanApex($legacy);
            $this->reconciliationService->assertHostnameActionable($apex, $apex);

            $hasPrimary = ApiDomainSetting::where('user_id', $legacy->user_id)->exists();

            $domain = ApiDomainSetting::create([
                'user_id' => $legacy->user_id,
                'custom_domain_id' => $legacy->id,
                'custom_name' => $apex,
                'status' => 'pending',
                'primary' => ! $hasPrimary,
                'ssl' => false,
                'added_date' => now(),
            ]);

            try {
                $provisionResult = $this->provisioningService->run(
                    $apex,
                    DomainProvisioningService::MODE_ADMIN_REPAIR
                );
            } catch (LockTimeoutException $exception) {
                $domain->delete();
                $request->session()->flash('error', __('domain_mutation.lock_timeout'));

                return back();
            }

            $this->domainSyncService->applyProvisioningResult(
                $domain,
                $provisionResult,
                $request,
                applyFailureThreshold: false
            );
            $this->domainCache->invalidateAdminCaches();

            \App\Support\TenantActivity::emit(
                $request,
                'domain.legacy_orphan_adopted',
                'api_domains_settings',
                $domain->id,
                null,
                $this->domainActivitySnapshot($domain->refresh())
            );

            $message = DomainHealthMessages::translate((string) ($provisionResult['message'] ?? ''));
            $outcome = (string) ($provisionResult['outcome'] ?? 'pending');

            if ($outcome === 'failed') {
                $request->session()->flash(
                    'error',
                    $message !== '' ? $message : __('domain_reconciliation.legacy_adopt_failed', ['domain' => $apex])
                );
            } else {
                $request->session()->flash(
                    'success',
                    $message !== '' ? $message : __('domain_reconciliation.legacy_adopted', ['domain' => $apex])
                );
            }
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());
        }

        return back();
    }

    public function deleteLegacyOrphan(Request $request)
    {
        $validated = $request->validate([
            'legacy_id' => ['required', 'integer', 'exists:user_custom_domains,id'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        try {
            $legacy = $this->resolveLegacyOrphanRow((int) $validated['legacy_id']);
            $apex = $this->resolveLegacyOrphanApex($legacy);

            $this->mutationGuard->assertCanMutate($request, $apex);
            $this->reconciliationService->assertHostnameActionable($apex, $apex);
            $this->vercelBackedGuard->assertLegacyDeleteSafe($legacy);

            if ($this->vercel->normalizeApex($validated['confirm_domain']) !== $apex) {
                $request->session()->flash(
                    'error',
                    __('domain_mutation.confirmation_required', ['domain' => $apex])
                );

                return back();
            }

            if (! $this->detachApexFromVercelByName($apex)) {
                $request->session()->flash('error', __('domain_admin.delete_provider_failed'));

                return back();
            }

            $before = [
                'legacy_id' => $legacy->id,
                'user_id' => $legacy->user_id,
                'requested_domain' => $legacy->requested_domain,
                'current_domain' => $legacy->current_domain,
            ];
            $legacyId = $legacy->id;
            $legacy->delete();
            $this->domainCache->invalidateAdminCaches();

            \App\Support\TenantActivity::emit(
                $request,
                'domain.legacy_orphan_deleted',
                'user_custom_domains',
                $legacyId,
                $before,
                null
            );

            $request->session()->flash(
                'success',
                __('domain_reconciliation.legacy_deleted', ['domain' => $apex])
            );
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());
        } catch (BusinessLogicException $e) {
            $request->session()->flash('error', $e->getMessage());
        }

        return back();
    }

    public function removeStrayWww(Request $request)
    {
        $validated = $request->validate([
            'www' => ['required', 'string', 'max:255'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        try {
            $normalizedWww = strtolower(trim($validated['www']));
            if (strtolower(trim($validated['confirm_domain'])) !== $normalizedWww) {
                throw new VercelDomainException(
                    __('domain_mutation.confirmation_required', ['domain' => $normalizedWww]),
                    internalCode: VercelDomainException::CODE_CONFIRMATION_REQUIRED
                );
            }

            $this->mutationGuard->assertConfigured();
            $this->mutationGuard->assertEnvironmentAllowsMutations();
            $this->mutationGuard->assertProjectIdentity();

            $result = $this->withProjectLock(function () use ($validated, $request) {
                return $this->reconciliationService->removeStrayWww(
                    $validated['www'],
                    $request,
                    'confirm_domain',
                    actor: 'admin:' . (string) (auth()->id() ?? 'unknown')
                );
            });

            if ($result['status'] === 'removed') {
                $request->session()->flash(
                    'success',
                    __('domain_reconciliation.stray_www_removed', ['domain' => $result['www']])
                );
            } else {
                $request->session()->flash(
                    'error',
                    __('domain_reconciliation.stray_www_remove_failed', [
                        'domain' => $result['www'],
                        'error' => $result['error'] ?? __('domain_reconciliation.unknown_error'),
                    ])
                );
            }
        } catch (VercelDomainException|ConnectionException $e) {
            $request->session()->flash('error', $e->getMessage());
        }

        return back();
    }

    public function fixWwwRedirect(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = ApiDomainSetting::findOrFail($validated['domain_id']);
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

        try {
            $this->mutationGuard->assertCanMutate($request, $apex);

            return $this->withProjectLock(function () use ($request, $domain, $apex) {
                $snapshot = $this->domainCache->fresh();
                if ($this->isInventoryUnreliable($snapshot)) {
                    throw new VercelDomainException(
                        __('vercel_capacity.inventory_unreliable'),
                        internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE
                    );
                }

                if (! in_array($apex, $snapshot['names'] ?? [], true)) {
                    throw new VercelDomainException(
                        __('domain_www.apex_not_on_vercel', ['domain' => $apex]),
                        internalCode: VercelDomainException::CODE_INVALID_DOMAIN
                    );
                }

                $wwwState = $this->resolveWwwStateForApex($snapshot, $apex);
                if (! $wwwState['present']) {
                    throw new VercelDomainException(
                        __('domain_www.fix_redirect_requires_www', ['domain' => $apex]),
                        internalCode: VercelDomainException::CODE_INVALID_DOMAIN
                    );
                }

                if ($wwwState['valid']) {
                    $request->session()->flash(
                        'success',
                        __('domain_www.redirect_already_correct', ['domain' => $apex])
                    );

                    return back();
                }

                $before = $this->domainActivitySnapshot($domain);
                $this->vercel->addDomain('www.' . $apex, $apex, 301);
                $this->domainCache->invalidateAdminCaches();

                \App\Support\TenantActivity::emit(
                    $request,
                    'domain.www_redirect_fixed',
                    'api_domains_settings',
                    $domain->id,
                    $before,
                    array_merge($this->domainActivitySnapshot($domain), ['www' => 'www.' . $apex])
                );

                $request->session()->flash(
                    'success',
                    __('domain_www.redirect_fixed', ['domain' => $apex])
                );

                return back();
            });
        } catch (VercelDomainException|ConnectionException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }
    }

    public function enableWww(Request $request)    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = ApiDomainSetting::findOrFail($validated['domain_id']);
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

        try {
            $this->mutationGuard->assertCanMutate($request, $apex);

            return $this->withProjectLock(function () use ($request, $domain, $apex) {
                $snapshot = $this->domainCache->fresh();
                if ($this->isInventoryUnreliable($snapshot)) {
                    throw new VercelDomainException(
                        __('vercel_capacity.inventory_unreliable'),
                        internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE
                    );
                }

                $wwwState = $this->resolveWwwStateForApex($snapshot, $apex);
                if (! in_array($apex, $snapshot['names'] ?? [], true)) {
                    throw new VercelDomainException(
                        __('domain_www.apex_not_on_vercel', ['domain' => $apex]),
                        internalCode: VercelDomainException::CODE_INVALID_DOMAIN
                    );
                }

                if ($wwwState['present']) {
                    if (! $wwwState['valid']) {
                        throw new VercelDomainException(
                            __('domain_mutation.redirect_mismatch', [
                                'domain' => 'www.' . $apex,
                                'expected_target' => $apex,
                                'expected_status' => '301',
                            ]),
                            internalCode: VercelDomainException::CODE_REDIRECT_MISMATCH
                        );
                    }

                    $request->session()->flash('success', __('domain_www.already_enabled', ['domain' => $apex]));

                    return back();
                }

                $freeEntries = $snapshot['metrics']['free_entries'] ?? null;
                if ($freeEntries !== null && $freeEntries < 1) {
                    throw new VercelDomainException(
                        __('domain_www.no_free_slot'),
                        internalCode: VercelDomainException::CODE_CAPACITY_REACHED
                    );
                }

                $before = $this->domainActivitySnapshot($domain);
                $this->vercel->addDomain('www.' . $apex, $apex, 301);
                $this->domainCache->invalidateAdminCaches();

                \App\Support\TenantActivity::emit(
                    $request,
                    'domain.www_enabled',
                    'api_domains_settings',
                    $domain->id,
                    $before,
                    array_merge($this->domainActivitySnapshot($domain), ['www' => 'www.' . $apex])
                );

                $request->session()->flash('success', __('domain_www.enabled', ['domain' => $apex]));

                return back();
            });
        } catch (VercelDomainException|ConnectionException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }
    }

    public function disableWww(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = ApiDomainSetting::findOrFail($validated['domain_id']);
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

        try {
            $this->mutationGuard->assertCanMutate($request, $apex);

            return $this->withProjectLock(function () use ($request, $domain, $apex) {
                $before = $this->domainActivitySnapshot($domain);
                $this->vercel->removeWwwHostname($apex);
                $this->domainCache->invalidateAdminCaches();

                \App\Support\TenantActivity::emit(
                    $request,
                    'domain.www_disabled',
                    'api_domains_settings',
                    $domain->id,
                    array_merge($before, ['www' => 'www.' . $apex]),
                    $this->domainActivitySnapshot($domain)
                );

                $request->session()->flash('success', __('domain_www.disabled', ['domain' => $apex]));

                return back();
            });
        } catch (VercelDomainException|ConnectionException $e) {
            $request->session()->flash('error', $e->getMessage());

            return back();
        }
    }

    public function cleanupVercelOrphan(Request $request)
    {
        $validated = $request->validate([
            'apex' => ['required', 'string', 'max:255'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->reconciliationService->removeVercelOnlyOrphan(
                $validated['apex'],
                $request,
                'confirm_domain',
                actor: 'admin:' . (string) (auth()->id() ?? 'unknown')
            );

            if ($result['status'] === 'removed') {
                $request->session()->flash(
                    'success',
                    __('domain_reconciliation.orphan_removed', ['domain' => $result['apex']])
                );
            } else {
                $request->session()->flash(
                    'error',
                    __('domain_reconciliation.orphan_remove_failed', [
                        'domain' => $result['apex'],
                        'error' => $result['error'] ?? __('domain_reconciliation.unknown_error'),
                    ])
                );
            }
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());
        }

        return back();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiDomainSetting>  $rows
     * @param  list<string>|null  $vercelNames
     * @return list<int>
     */
    private function filterDomainIdsByHealth($rows, string $healthFilter, ?array $vercelNames): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $hint = null;
            if ($vercelNames !== null) {
                $apex = $this->vercel->normalizeApex((string) $row->custom_name);
                $hint = in_array($apex, $vercelNames, true);
            }

            $code = $row->health($hint)['code'];

            if ($healthFilter === 'issues') {
                if (! in_array($code, ['linked', 'checks_disabled', 'unchecked', 'apex_only'], true)) {
                    $ids[] = $row->id;
                }
            } elseif ($code === $healthFilter || ($healthFilter === 'ns_mismatch' && $code === 'ns_not_pointing')) {
                $ids[] = $row->id;
            }
        }

        if ($ids === []) {
            return [0];
        }

        return $ids;
    }

    /**
     * @return array{linked: int, apex_only: int, confirmed_issues: int, unchecked: int, db_domain_count: int, by_code: array<string, int>}
     */
    private function resolveDomainHealthCounts(?array $inventorySnapshot): array
    {
        $cacheKey = $this->domainCache->healthCountersKey();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($inventorySnapshot) {
            $vercelNames = $inventorySnapshot['names'] ?? null;

            $rows = ApiDomainSetting::query()
                ->select(['id', 'custom_name', 'status', 'dns_records', 'expires_at'])
                ->get();

            $linked = 0;
            $apexOnly = 0;
            $confirmedIssues = 0;
            $byCode = [];

            foreach ($rows as $row) {
                $hint = null;
                if ($vercelNames !== null) {
                    $apex = $this->vercel->normalizeApex((string) $row->custom_name);
                    $hint = in_array($apex, $vercelNames, true);
                }

                $health = $row->health($hint);
                $code = $health['code'];
                $byCode[$code] = ($byCode[$code] ?? 0) + 1;

                if ($code === 'linked') {
                    $linked++;
                } elseif ($code === 'apex_only') {
                    $apexOnly++;
                } elseif (! in_array($code, ['checks_disabled', 'unchecked', 'apex_only'], true)) {
                    $confirmedIssues++;
                }
            }

            ksort($byCode);

            return [
                'linked' => $linked,
                'apex_only' => $apexOnly,
                'confirmed_issues' => $confirmedIssues,
                'unchecked' => $byCode['unchecked'] ?? 0,
                'db_domain_count' => $rows->count(),
                'by_code' => $byCode,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function buildCapacity(array $snapshot): array
    {
        $metrics = is_array($snapshot['metrics'] ?? null) ? $snapshot['metrics'] : [];
        $entriesUsed = (int) ($metrics['total_entries'] ?? $snapshot['count'] ?? 0);
        $entriesTotal = config('services.vercel.max_project_domains');
        $entriesTotal = $entriesTotal !== null ? (int) $entriesTotal : null;
        $freeEntries = $metrics['free_entries'] ?? null;
        $customerApex = (int) ($metrics['customer_apex'] ?? 0);
        $wwwRedirects = (int) ($metrics['www_redirects'] ?? 0);
        $platformEntries = (int) ($metrics['platform_entries'] ?? 0);
        $isLowerBound = (bool) ($metrics['is_lower_bound'] ?? $snapshot['is_lower_bound'] ?? false);

        $usagePercent = ($entriesTotal !== null && $entriesTotal > 0)
            ? ($entriesUsed / $entriesTotal) * 100
            : null;

        $alertClass = 'success';
        if ($entriesTotal !== null) {
            if (($freeEntries !== null && $freeEntries === 0) || ($usagePercent !== null && $usagePercent >= 95)) {
                $alertClass = 'danger';
            } elseif ($usagePercent !== null && $usagePercent >= 80) {
                $alertClass = 'warning';
            }
        }

        return [
            'entries_used' => $entriesUsed,
            'entries_total' => $entriesTotal,
            'free_entries' => $freeEntries,
            'customer_apex' => $customerApex,
            'www_redirects' => $wwwRedirects,
            'platform_entries' => $platformEntries,
            'is_lower_bound' => $isLowerBound,
            'usage_percent' => $usagePercent ?? 0,
            'has_cap' => $entriesTotal !== null,
            'alert_class' => $alertClass,
            'fetched_at' => $snapshot['fetched_at'] ?? null,
        ];
    }

    private function normalizeHealthFilter(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $filter = (string) $value;

        return in_array($filter, self::HEALTH_FILTERS, true) ? $filter : null;
    }

    private function isInventoryUnreliable(?array $snapshot): bool
    {
        if ($snapshot === null) {
            return true;
        }

        if (($snapshot['is_lower_bound'] ?? false) === true) {
            return true;
        }

        $fetchedAt = $snapshot['fetched_at'] ?? null;
        if (! is_string($fetchedAt) || $fetchedAt === '') {
            return true;
        }

        return Carbon::parse($fetchedAt)->diffInSeconds(now()) > self::INVENTORY_TTL_SECONDS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiDomainSetting>  $rows
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>|null  $capacity
     * @return array<int, array{needs_capacity_confirm: bool}>
     */
    private function resolveRepairActionsForRows($rows, ?array $snapshot, ?array $capacity): array
    {
        $vercelNames = $snapshot['names'] ?? [];
        $vercelSet = array_fill_keys($vercelNames, true);
        $needsConfirm = ($capacity['has_cap'] ?? false) === true
            && ($capacity['free_entries'] ?? null) === 0;

        $actions = [];
        foreach ($rows as $row) {
            $apex = $this->vercel->normalizeApex((string) $row->custom_name);
            $actions[$row->id] = [
                'needs_capacity_confirm' => $needsConfirm && ! isset($vercelSet[$apex]),
            ];
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDomainDiagnostics(ApiDomainSetting $domain): array
    {
        $dnsRecords = is_array($domain->dns_records) ? $domain->dns_records : [];
        $lastCheck = is_array($dnsRecords['last_check'] ?? null) ? $dnsRecords['last_check'] : [];
        $provisioning = is_array($lastCheck['provisioning'] ?? null)
            ? $lastCheck['provisioning']
            : (is_array($dnsRecords['provisioning'] ?? null) ? $dnsRecords['provisioning'] : []);
        $expectedNs = array_values((array) config('services.vercel.nameservers', []));
        $observedNs = array_values((array) ($lastCheck['observed_nameservers'] ?? []));
        $recommendedDns = ApiDomainSetting::nameserverInstructions();
        $ownershipChallenge = $lastCheck['ownership_challenge'] ?? null;
        $health = $domain->health();

        return [
            'domain_id' => $domain->id,
            'custom_name' => $domain->custom_name,
            'status' => $domain->status,
            'ssl' => (bool) $domain->ssl,
            'health' => $health,
            'last_check_at' => $lastCheck['last_check_at'] ?? null,
            'provider_reachable' => array_key_exists('provider_reachable', $lastCheck)
                ? (bool) $lastCheck['provider_reachable']
                : null,
            'apex_attached' => (bool) ($lastCheck['apex_attached'] ?? $lastCheck['vercel_attached'] ?? false),
            'apex_verified' => (bool) ($lastCheck['apex_verified'] ?? $lastCheck['vercel_verified'] ?? false),
            'zone_enabled' => (bool) ($lastCheck['zone_enabled'] ?? false),
            'account_domain_present' => (bool) ($lastCheck['account_domain_present'] ?? false),
            'observed_nameservers' => $observedNs,
            'expected_nameservers' => $expectedNs,
            'nameservers_ok' => (bool) ($lastCheck['nameservers_ok'] ?? false),
            'nameserver_check_enabled' => (bool) ($lastCheck['nameserver_check_enabled'] ?? true),
            'dns_misconfigured' => (bool) ($lastCheck['dns_misconfigured'] ?? false),
            'configured_by' => $lastCheck['configured_by'] ?? null,
            'recommended_ipv4' => array_values((array) ($lastCheck['recommended_ipv4'] ?? [])),
            'recommended_cname' => array_values((array) ($lastCheck['recommended_cname'] ?? [])),
            'recommended_dns' => $recommendedDns,
            'ownership_challenge' => is_array($ownershipChallenge) ? $ownershipChallenge : null,
            'certificate_readiness' => $lastCheck['certificate_readiness'] ?? null,
            'certificate_id' => $lastCheck['certificate_id'] ?? null,
            'ssl_ready' => (bool) ($lastCheck['ssl_ready'] ?? false),
            'provisioning' => $provisioning,
            'consecutive_failures' => (int) ($lastCheck['consecutive_failures'] ?? 0),
            'first_failure_at' => $lastCheck['first_failure_at'] ?? null,
            'failure_threshold' => max(1, (int) config('services.vercel.health_failure_threshold', 3)),
            'outcome' => $lastCheck['outcome'] ?? null,
            'health_code' => $lastCheck['health_code'] ?? $health['code'],
            'message' => DomainHealthMessages::translate((string) ($lastCheck['message'] ?? $health['reason'] ?? '')),
            'has_last_check' => $lastCheck !== [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ApiDomainSetting>  $rows
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>|null  $capacity
     * @return array<int, array{mode: string, present: bool, valid: bool, can_enable: bool, can_disable: bool, can_fix_redirect: bool}>
     */
    private function resolveWwwStatesForRows($rows, ?array $snapshot, ?array $capacity, bool $inventoryUnreliable): array
    {
        $states = [];
        $freeEntries = $capacity['free_entries'] ?? null;
        $canAllocate = ! $inventoryUnreliable
            && ($freeEntries === null || $freeEntries > 0);

        foreach ($rows as $row) {
            $apex = $this->vercel->normalizeApex((string) $row->custom_name);
            $wwwState = $snapshot !== null
                ? $this->resolveWwwStateForApex($snapshot, $apex)
                : ['present' => false, 'valid' => false];

            $states[$row->id] = [
                'mode' => $wwwState['present'] && $wwwState['valid'] ? 'apex_and_www' : 'apex_only',
                'present' => $wwwState['present'],
                'valid' => $wwwState['valid'],
                'can_enable' => ! $wwwState['present'] && $canAllocate,
                'can_disable' => $wwwState['present'] && $wwwState['valid'],
                'can_fix_redirect' => $wwwState['present'] && ! $wwwState['valid'],
            ];
        }

        return $states;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{present: bool, valid: bool}
     */
    private function resolveWwwStateForApex(array $snapshot, string $apex): array
    {
        $www = 'www.' . $apex;

        foreach ($snapshot['domains'] ?? [] as $domain) {
            if ((string) ($domain['name'] ?? '') !== $www) {
                continue;
            }

            $redirectTarget = strtolower((string) ($domain['redirect'] ?? ''));
            $statusCode = isset($domain['redirectStatusCode']) ? (int) $domain['redirectStatusCode'] : null;
            $valid = filled($domain['redirect'] ?? null)
                && $redirectTarget === $apex
                && ($statusCode === null || in_array($statusCode, [301, 308], true));

            return ['present' => true, 'valid' => $valid];
        }

        return ['present' => false, 'valid' => false];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function buildReconciliationSummary(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $report = $this->reconciliationService->buildReportFromSnapshot($snapshot);

        $ownershipRequired = [];
        $dnsIssues = [];

        $vercelNames = $snapshot['names'] ?? [];
        $vercelSet = array_fill_keys($vercelNames, true);

        $dbRows = ApiDomainSetting::query()
            ->select(['id', 'custom_name', 'status', 'dns_records'])
            ->orderBy('id')
            ->get();

        foreach ($dbRows as $row) {
            $apex = $this->vercel->normalizeApex((string) $row->custom_name);
            $hint = isset($vercelSet[$apex]) ? true : false;
            $health = $row->health($hint);
            $code = $health['code'];

            if ($code === 'ownership_required') {
                $dnsRecords = is_array($row->dns_records) ? $row->dns_records : [];
                $lastCheck = is_array($dnsRecords['last_check'] ?? null) ? $dnsRecords['last_check'] : [];
                $challenge = $lastCheck['ownership_challenge'] ?? null;

                $ownershipRequired[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'apex' => $apex,
                    'ownership_challenge' => is_array($challenge) ? $challenge : null,
                ];
            }
            if ($code === 'dns_misconfigured') {
                $dnsIssues[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'apex' => $apex,
                ];
            }
        }

        return array_merge($report, [
            'summary' => array_merge($report['summary'], [
                'ownership_required' => count($ownershipRequired),
                'dns_issues' => count($dnsIssues),
                'vercel_only' => $report['summary']['vercel_only_orphan'] ?? count($report['vercel_only_orphan'] ?? []),
            ]),
            'ownership_required' => $ownershipRequired,
            'dns_issues' => $dnsIssues,
            'vercel_only' => collect($report['vercel_only_orphan'] ?? [])
                ->map(fn (array $entry) => [
                    'name' => $entry['apex'],
                    'apex' => $entry['apex'],
                    'vercel_names' => $entry['vercel_names'] ?? [],
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @template TReturn
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function withProjectLock(callable $callback)
    {
        try {
            return $this->domainCache->withMutationLock($callback);
        } catch (LockTimeoutException $e) {
            throw new VercelDomainException(
                __('domain_mutation.lock_timeout'),
                internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE
            );
        }
    }

    // public function index(Request $request)
    // {
    //     $rcDomains = UserCustomDomain::orderBy('id', 'DESC')
    //         ->when($request->domain, function ($query) use ($request) {
    //             return $query->where(function ($query) use ($request) {
    //                 $query->where('current_domain', 'LIKE', '%' . $request->domain . '%')
    //                     ->orWhere('requested_domain', 'LIKE', '%' . $request->domain . '%');
    //             });
    //         })
    //         ->when($request->username, function ($query) use ($request) {
    //             return $query->whereHas('user', function ($query) use ($request) {
    //                 $query->where('username', $request->username);
    //             });
    //         });
    //     if (empty($request->type)) {
    //         $rcDomains = $rcDomains->paginate(10);
    //     } elseif ($request->type == 'pending') {
    //         $rcDomains = $rcDomains->where('status', 0)->paginate(10);
    //     } elseif ($request->type == 'connected') {
    //         $rcDomains = $rcDomains->where('status', 1)->paginate(10);
    //     } elseif ($request->type == 'rejected') {
    //         $rcDomains = $rcDomains->where('status', 2)->paginate(10);
    //     } else {
    //         return view('errors.404');
    //     }
    //     $data['rcDomains'] = $rcDomains;
    //     dd($data);
    //     return view('admin.domains.custom', $data);
    // }
    public function mail(Request $request)
    {
        $rules = [
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $be = BasicExtended::first();
        $from = $be->from_mail;

        $sub = $request->subject;
        $msg = $request->message;
        $to = $request->email;

        // Send Mail
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        if ($be->is_smtp == 1) {
            try {
                $mail->isSMTP();
                $mail->Host       = $be->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $be->smtp_username;
                $mail->Password   = $be->smtp_password;
                $mail->SMTPSecure = $be->encryption;
                $mail->Port       = $be->smtp_port;

                //Recipients
                $mail->setFrom($from);
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $sub;
                $mail->Body    = $msg;

                $mail->send();
            } catch (\Exception $e) {
            }
        } else {
            try {

                //Recipients
                $mail->setFrom($from);
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $sub;
                $mail->Body    = $msg;

                $mail->send();
            } catch (\Exception $e) {
            }
        }

        Session::flash('success', __('domain_admin.mail_sent'));
        return "success";
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:api_domains_settings,id'],
            'confirm_domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = ApiDomainSetting::findOrFail($validated['domain_id']);

        try {
            $this->mutationGuard->assertCanMutate($request, (string) $domain->custom_name);
        } catch (VercelDomainException $e) {
            $request->session()->flash('error', $e->getMessage());

            return redirect()->back();
        }

        if (! $this->detachFromVercel($domain)) {
            $request->session()->flash('error', __('domain_admin.delete_provider_failed'));
            return redirect()->back();
        }

        $this->deleteDomainRow($request, $domain);
        $this->domainCache->invalidateAdminCaches();

        $request->session()->flash('success', __('domain_admin.deleted'));
        return redirect()->back();
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:api_domains_settings,id'],
            'confirm_domains' => ['required', 'array', 'min:1'],
            'confirm_domains.*' => ['required', 'string', 'max:255'],
        ]);

        $ids = $validated['ids'];
        $domains = ApiDomainSetting::whereIn('id', $ids)->get();

        $expected = $domains
            ->map(fn (ApiDomainSetting $domain) => $this->vercel->normalizeApex((string) $domain->custom_name))
            ->sort()
            ->values();
        $provided = collect($validated['confirm_domains'])
            ->map(fn (string $domain) => $this->vercel->normalizeApex($domain))
            ->sort()
            ->values();

        if ($expected->toArray() !== $provided->toArray()) {
            return response()->json(['message' => __('domain_mutation.confirmation_required', ['domain' => $expected->implode(', ')])], 422);
        }

        try {
            $this->mutationGuard->assertCanMutate($request);
        } catch (VercelDomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Deliberately not wrapped in a transaction: Vercel removal cannot be
        // rolled back, so a DB rollback after a successful detach would leave
        // rows pointing at domains the project no longer serves. Each domain is
        // detached and deleted independently instead.
        $failed = [];
        foreach ($domains as $domain) {
            if (! $this->detachFromVercel($domain)) {
                $failed[] = $domain->custom_name;
                continue;
            }

            $this->deleteDomainRow($request, $domain);
        }

        if ($failed !== []) {
            $request->session()->flash('error', __('domain_admin.bulk_delete_partial', ['domains' => implode(', ', $failed)]));
            return "error";
        }

        $this->domainCache->invalidateAdminCaches();

        $request->session()->flash('success', __('domain_admin.bulk_deleted'));
        return "success";
    }

    /**
     * After external detachment succeeds: lock tenant rows, delete, reassign primary.
     */
    private function deleteDomainRow(Request $request, ApiDomainSetting $domain): void
    {
        DB::transaction(function () use ($request, $domain) {
            ApiDomainSetting::where('user_id', $domain->user_id)->lockForUpdate()->get();

            $row = ApiDomainSetting::where('id', $domain->id)->lockForUpdate()->first();
            if ($row === null) {
                return;
            }

            $before = $this->domainActivitySnapshot($row);
            $wasPrimary = (bool) $row->primary;
            $id = $row->id;
            $userId = $row->user_id;

            $row->delete();

            if ($wasPrimary) {
                $replacement = ApiDomainSetting::where('user_id', $userId)
                    ->preferredActive()
                    ->lockForUpdate()
                    ->first();

                if ($replacement) {
                    $replacement->primary = true;
                    $replacement->save();
                }
            }

            \App\Support\TenantActivity::emit($request, 'domain.deleted', 'api_domains_settings', $id, $before, null);
        });
    }

    /**
     * @return array{custom_name: string, status: string, primary: bool, ssl: bool}
     */
    private function domainActivitySnapshot(ApiDomainSetting $domain): array
    {
        return [
            'custom_name' => $this->vercel->normalizeApex((string) $domain->custom_name),
            'status' => (string) $domain->status,
            'primary' => (bool) $domain->primary,
            'ssl' => (bool) $domain->ssl,
        ];
    }

    /**
     * Detach apex + www from Vercel. Fails closed: returns false so the caller
     * keeps the row rather than orphaning the domain on the Vercel project.
     */
    private function detachFromVercel(ApiDomainSetting $domain): bool
    {
        if (! (bool) config('services.vercel.auto_attach_custom_domain', true) || ! $this->vercel->isConfigured()) {
            return true;
        }

        try {
            $this->vercel->removeApexAndWww((string) $domain->custom_name);
        } catch (VercelDomainException|ConnectionException $e) {
            Log::warning('Failed to remove domain from Vercel during admin delete', [
                'domain_id' => $domain->id,
                'domain' => $domain->custom_name,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return false;
        }

        return true;
    }

    private function resolveLegacyOrphanRow(int $legacyId): CustomDomain
    {
        $legacy = CustomDomain::query()->findOrFail($legacyId);

        if ($legacy->apiDomainSetting()->exists()) {
            throw new VercelDomainException(
                __('domain_reconciliation.legacy_not_orphan', ['id' => $legacyId]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        $apex = $this->resolveLegacyOrphanApex($legacy);

        if (ApiDomainSetting::query()->where('custom_name', $apex)->exists()) {
            throw new VercelDomainException(
                __('domain_reconciliation.legacy_name_taken', ['domain' => $apex]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        return $legacy;
    }

    private function resolveLegacyOrphanApex(CustomDomain $legacy): string
    {
        $candidate = filled($legacy->current_domain)
            ? (string) $legacy->current_domain
            : (string) ($legacy->requested_domain ?? '');

        $apex = $this->vercel->normalizeApex($candidate);

        if ($apex === '') {
            throw new VercelDomainException(
                __('domain_reconciliation.legacy_missing_domain', ['id' => $legacy->id]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        return $apex;
    }

    private function detachApexFromVercelByName(string $apex): bool
    {
        if (! (bool) config('services.vercel.auto_attach_custom_domain', true) || ! $this->vercel->isConfigured()) {
            return true;
        }

        try {
            $this->reconciliationService->assertHostnameActionable($apex, $apex);
            $this->vercel->removeApexAndWww($apex);
        } catch (VercelDomainException|ConnectionException $e) {
            Log::warning('Failed to remove domain from Vercel during legacy orphan delete', [
                'domain' => $apex,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return false;
        }

        return true;
    }
}
