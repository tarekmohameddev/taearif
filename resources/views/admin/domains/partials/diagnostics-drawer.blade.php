@php
    $d = $diagnostics ?? [];
    $formatDiagnosticsDate = function ($value): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) && ! is_numeric($value) && ! ($value instanceof \DateTimeInterface)) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)
                ->locale(app()->getLocale())
                ->timezone(config('app.timezone'))
                ->format('d/m/Y, g:i A');
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    };
    $looksLikeIsoDate = function ($value): bool {
        return is_string($value)
            && (bool) preg_match('/^\d{4}-\d{2}-\d{2}([T\s]\d{2}:\d{2}(:\d{2})?)?/', $value);
    };
    $boolBadge = function (?bool $value, string $trueLabel = null, string $falseLabel = null): string {
        if ($value === null) {
            return '<span class="badge badge-secondary">' . e(__('domain_diagnostics.unknown')) . '</span>';
        }

        return $value
            ? '<span class="badge badge-success">' . e($trueLabel ?? __('Yes')) . '</span>'
            : '<span class="badge badge-danger">' . e($falseLabel ?? __('No')) . '</span>';
    };
    $health = $d['health'] ?? [];
    $healthBadgeClass = 'badge badge-' . ($health['class'] ?? 'secondary');
    if (($health['class'] ?? '') === 'warning') {
        $healthBadgeClass .= ' text-dark';
    }
    $provisioning = is_array($d['provisioning'] ?? null) ? $d['provisioning'] : [];
    $recommendedDns = is_array($d['recommended_dns'] ?? null) ? $d['recommended_dns'] : [];
    $ownershipChallenge = is_array($d['ownership_challenge'] ?? null) ? $d['ownership_challenge'] : null;
    $observedNs = $d['observed_nameservers'] ?? [];
    $expectedNs = $d['expected_nameservers'] ?? [];
    $recommendedIpv4 = $d['recommended_ipv4'] ?? [];
    $recommendedCname = $d['recommended_cname'] ?? [];
    $lastCheckAtDisplay = $formatDiagnosticsDate($d['last_check_at'] ?? null);
    $firstFailureAtDisplay = $formatDiagnosticsDate($d['first_failure_at'] ?? null);
@endphp
<div class="domain-diagnostics-drawer">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1">{{ __('domain_diagnostics.title') }}</h5>
            <p class="text-muted small mb-0"><bdi dir="ltr">{{ $d['custom_name'] ?? $domain->custom_name ?? '—' }}</bdi></p>
        </div>
        <span class="{{ $healthBadgeClass }}">{{ $health['label'] ?? __('domain_health.unchecked') }}</span>
    </div>

    @if (empty($d['has_last_check']))
        <div class="alert alert-secondary mb-0">{{ __('domain_diagnostics.no_last_check') }}</div>
    @else
        @if ($lastCheckAtDisplay)
            <p class="text-muted small mb-0">
                {{ __('domain_diagnostics.last_check_at') }}:
                <bdi dir="ltr">{{ $lastCheckAtDisplay }}</bdi>
            </p>
        @endif
        @if (! empty($d['message']))
            <p class="small mb-3" dir="auto">{{ $d['message'] }}</p>
        @endif

        @php
            $healthCode = $d['health_code'] ?? ($health['code'] ?? 'unchecked');
            $meaningKey = "domain_diagnostics.meaning.{$healthCode}";
            $actionKey = "domain_diagnostics.action.{$healthCode}";
            $meaningText = __($meaningKey);
            if ($meaningText === $meaningKey) {
                $meaningText = __('domain_diagnostics.meaning.default');
            }
            $actionText = __($actionKey);
            if ($actionText === $actionKey) {
                $actionText = __('domain_diagnostics.action.default');
            }
            $meaningAlertClass = 'alert-' . ($health['class'] ?? 'secondary');
            if (($health['class'] ?? '') === 'warning') {
                $meaningAlertClass = 'alert-warning';
            }
        @endphp
        <div class="alert {{ $meaningAlertClass }} domain-diagnostics-meaning" dir="auto">
            <div class="mb-1">
                <strong>{{ __('domain_diagnostics.meaning_title') }}:</strong>
                <span>{{ $meaningText }}</span>
            </div>
            <div>
                <strong>{{ __('domain_diagnostics.action_title') }}:</strong>
                <span>{{ $actionText }}</span>
            </div>
        </div>

        @php
            // Issues we can act on from here. Repair & Verify re-runs the guarded
            // orchestration (enable zone, attach, issue cert, re-check); the claim
            // button posts the ownership claim. Healthy/config states need nothing.
            $repairCodes = ['zone_disabled', 'certificate_pending', 'certificate_error', 'unverified', 'not_on_vercel', 'dns_misconfigured', 'ns_not_pointing', 'ns_mismatch', 'provider_error', 'unchecked'];
            $showRepair = in_array($healthCode, $repairCodes, true);
            $showClaim = $healthCode === 'ownership_required';
            $noActionCodes = ['linked', 'apex_only', 'checks_disabled'];
            $domainId = $d['domain_id'] ?? ($domain->id ?? null);
        @endphp
        @if (($showRepair || $showClaim) && $domainId)
            <div class="domain-diagnostics-actions mb-3 d-flex flex-wrap align-items-center">
                @if ($showRepair)
                    <form action="{{ route('admin.custom-domain.repair-verify') }}" method="POST" class="mr-2 mb-1">
                        @csrf
                        <input type="hidden" name="domain_id" value="{{ $domainId }}">
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fas fa-wrench mr-1"></i>{{ __('domain_health.repair_verify') }}
                        </button>
                    </form>
                @endif
                @if ($showClaim)
                    <form action="{{ route('admin.custom-domain.claim-ownership') }}" method="POST" class="mr-2 mb-1">
                        @csrf
                        <input type="hidden" name="domain_id" value="{{ $domainId }}">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fas fa-key mr-1"></i>{{ __('domain_admin.claim_ownership') }}
                        </button>
                    </form>
                @endif
                <span class="small text-muted mb-1">{{ __('domain_diagnostics.action_hint') }}</span>
            </div>
        @elseif (in_array($healthCode, $noActionCodes, true))
            <p class="text-success small mb-3"><i class="fas fa-check-circle mr-1"></i>{{ __('domain_diagnostics.no_action_needed') }}</p>
        @endif

        <table class="table table-sm table-striped mb-3 domain-diagnostics-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('domain_diagnostics.col_check') }}</th>
                    <th scope="col">{{ __('domain_diagnostics.col_value') }}</th>
                    <th scope="col">{{ __('domain_diagnostics.col_meaning') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.provider_reachable') }}</th>
                    <td>{!! $boolBadge($d['provider_reachable'] ?? null) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_provider_reachable') }}</td>
                </tr>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.apex_attached') }}</th>
                    <td>{!! $boolBadge($d['apex_attached'] ?? false) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_apex_attached') }}</td>
                </tr>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.apex_verified') }}</th>
                    <td>{!! $boolBadge($d['apex_verified'] ?? false) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_apex_verified') }}</td>
                </tr>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.zone_enabled') }}</th>
                    <td>{!! $boolBadge($d['zone_enabled'] ?? false) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_zone_enabled') }}</td>
                </tr>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.nameservers_ok') }}</th>
                    <td>
                        @if (($d['nameserver_check_enabled'] ?? true) === false)
                            <span class="badge badge-secondary">{{ __('domain_health.checks_disabled') }}</span>
                        @else
                            {!! $boolBadge($d['nameservers_ok'] ?? false) !!}
                        @endif
                    </td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_nameservers_ok') }}</td>
                </tr>
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.dns_misconfigured') }}</th>
                    <td>{!! $boolBadge(! ($d['dns_misconfigured'] ?? false), __('Yes'), __('No')) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_dns_misconfigured') }}</td>
                </tr>
                @if (! empty($d['configured_by']))
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.configured_by') }}</th>
                    <td><code>{{ $d['configured_by'] }}</code></td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_configured_by') }}</td>
                </tr>
                @endif
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.ssl_ready') }}</th>
                    <td>{!! $boolBadge($d['ssl_ready'] ?? false) !!}</td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_ssl_ready') }}</td>
                </tr>
                @if (! empty($d['certificate_readiness']))
                @php
                    $cr = (string) $d['certificate_readiness'];
                    $crClass = $cr === 'issued' ? 'success' : ($cr === 'certificate_error' ? 'danger' : 'warning');
                    if ($crClass === 'warning') { $crClass .= ' text-dark'; }
                @endphp
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.certificate_readiness') }}</th>
                    <td><span class="badge badge-{{ $crClass }}">{{ $cr }}</span></td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_certificate_readiness') }}</td>
                </tr>
                @endif
                @if (! empty($d['certificate_id']))
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.certificate_id') }}</th>
                    <td><code>{{ $d['certificate_id'] }}</code></td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_certificate_id') }}</td>
                </tr>
                @endif
                <tr>
                    <th scope="row">{{ __('domain_diagnostics.consecutive_failures') }}</th>
                    <td>
                        {{ $d['consecutive_failures'] ?? 0 }}
                        / {{ $d['failure_threshold'] ?? 3 }}
                        @if ($firstFailureAtDisplay)
                            <small class="text-muted d-block">
                                {{ __('domain_diagnostics.first_failure_at') }}:
                                <bdi dir="ltr">{{ $firstFailureAtDisplay }}</bdi>
                            </small>
                        @endif
                    </td>
                    <td class="small text-muted" dir="auto">{{ __('domain_diagnostics.help_consecutive_failures') }}</td>
                </tr>
            </tbody>
        </table>

        <h6 class="mt-3">{{ __('domain_diagnostics.nameservers') }}</h6>
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="small text-muted mb-1">{{ __('domain_diagnostics.expected_ns') }}</p>
                @if ($expectedNs === [])
                    <p class="small mb-0">—</p>
                @else
                    <ul class="small mb-0 pl-3">
                        @foreach ($expectedNs as $ns)
                            <li><code>{{ $ns }}</code></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="col-md-6">
                <p class="small text-muted mb-1">{{ __('domain_diagnostics.observed_ns') }}</p>
                @if ($observedNs === [])
                    <p class="small mb-0">—</p>
                @else
                    <ul class="small mb-0 pl-3">
                        @foreach ($observedNs as $ns)
                            <li><code>{{ $ns }}</code></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <p class="small text-muted mb-3" dir="auto"><i class="fas fa-info-circle mr-1"></i>{{ __('domain_diagnostics.ns_order_note') }}</p>

        @if (($d['dns_misconfigured'] ?? false) && ($recommendedIpv4 !== [] || $recommendedCname !== []))
            <h6>{{ __('domain_diagnostics.recommended_records') }}</h6>
            @if ($recommendedIpv4 !== [])
                <p class="small text-muted mb-1">{{ $recommendedDns['recommended_a_label'] ?? 'A' }}</p>
                <ul class="small pl-3">
                    @foreach ($recommendedIpv4 as $record)
                        @if (is_array($record))
                            <li><code>{{ json_encode($record) }}</code></li>
                        @else
                            <li><code>{{ $record }}</code></li>
                        @endif
                    @endforeach
                </ul>
            @endif
            @if ($recommendedCname !== [])
                <p class="small text-muted mb-1">{{ $recommendedDns['recommended_cname_label'] ?? 'CNAME' }}</p>
                <ul class="small pl-3">
                    @foreach ($recommendedCname as $record)
                        @if (is_array($record))
                            <li><code>{{ json_encode($record) }}</code></li>
                        @else
                            <li><code>{{ $record }}</code></li>
                        @endif
                    @endforeach
                </ul>
            @endif
        @elseif (($d['nameserver_check_enabled'] ?? true) && ! ($d['nameservers_ok'] ?? false) && ($recommendedDns['nameservers'] ?? []) !== [])
            <h6>{{ __('domain_diagnostics.recommended_records') }}</h6>
            <p class="small text-muted">{{ __('domain_diagnostics.use_expected_ns') }}</p>
            <ul class="small pl-3">
                @foreach ($recommendedDns['nameservers'] as $ns)
                    <li><code>{{ $ns }}</code></li>
                @endforeach
            </ul>
        @endif

        @if ($ownershipChallenge !== null && $ownershipChallenge !== [])
            <h6 class="mt-3">{{ $recommendedDns['ownership_txt_label'] ?? __('domain_diagnostics.ownership_txt') }}</h6>
            <table class="table table-sm table-bordered mb-3">
                <tbody>
                    <tr>
                        <th>{{ $recommendedDns['record_type_label'] ?? 'Type' }}</th>
                        <td><code>{{ $ownershipChallenge['type'] ?? 'txt' }}</code></td>
                    </tr>
                    <tr>
                        <th>{{ $recommendedDns['record_name_label'] ?? 'Name' }}</th>
                        <td><code>{{ $ownershipChallenge['domain'] ?? '—' }}</code></td>
                    </tr>
                    <tr>
                        <th>{{ $recommendedDns['record_value_label'] ?? 'Value' }}</th>
                        <td><code class="text-break">{{ $ownershipChallenge['value'] ?? '—' }}</code></td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if ($provisioning !== [])
            <h6 class="mt-3">{{ __('domain_diagnostics.provisioning_ledger') }}</h6>
            <p class="small text-muted mb-2" dir="auto">{{ __('domain_diagnostics.provisioning_ledger_hint') }}</p>
            <table class="table table-sm table-striped mb-0 domain-diagnostics-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('domain_diagnostics.col_check') }}</th>
                        <th scope="col">{{ __('domain_diagnostics.col_value') }}</th>
                        <th scope="col">{{ __('domain_diagnostics.col_meaning') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($provisioning as $key => $value)
                        @if (is_scalar($value) || $value === null)
                            @php
                                $labelKey = "domain_diagnostics.ledger_label.{$key}";
                                $fieldLabel = __($labelKey);
                                if ($fieldLabel === $labelKey) { $fieldLabel = $key; }

                                $meaningKey = "domain_diagnostics.ledger_meaning.{$key}";
                                $fieldMeaning = __($meaningKey);
                                if ($fieldMeaning === $meaningKey) { $fieldMeaning = ''; }

                                if ($value === null || $value === '') {
                                    $displayValue = '—';
                                } elseif ($value === true) {
                                    $displayValue = __('Yes');
                                } elseif ($value === false) {
                                    $displayValue = __('No');
                                } elseif ($looksLikeIsoDate($value)) {
                                    $displayValue = $formatDiagnosticsDate($value) ?? (string) $value;
                                } else {
                                    $displayValue = (string) $value;
                                }

                                // Plain-language gloss for known tokens (created / pre_existing / modes …).
                                $valueGloss = '';
                                if (is_string($value) && $value !== '' && ! $looksLikeIsoDate($value)) {
                                    $valueKey = "domain_diagnostics.ledger_value.{$value}";
                                    $translated = __($valueKey);
                                    if ($translated !== $valueKey) { $valueGloss = $translated; }
                                }
                            @endphp
                            <tr>
                                <th scope="row">{{ $fieldLabel }}</th>
                                <td>
                                    @if ($looksLikeIsoDate($value))
                                        <bdi dir="ltr">{{ $displayValue }}</bdi>
                                    @else
                                        <code>{{ $displayValue }}</code>
                                    @endif
                                    @if ($valueGloss !== '')
                                        <span class="small text-muted d-block" dir="auto">{{ $valueGloss }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted" dir="auto">{{ $fieldMeaning }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</div>
