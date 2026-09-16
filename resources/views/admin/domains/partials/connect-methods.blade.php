@php
    // Self-contained "how to connect" guide shown inside the per-domain diagnostics
    // drawer. It explains BOTH supported methods with step-by-step instructions and
    // the concrete records for THIS domain. Record values come from the last Vercel
    // check when available, otherwise from Vercel's documented defaults so the guide
    // is useful even before the first check has run.
    $d = $d ?? [];
    $recommendedDns = is_array($d['recommended_dns'] ?? null) ? $d['recommended_dns'] : [];
    $expectedNs = array_values((array) ($d['expected_nameservers'] ?? []));
    $methodNs = ($recommendedDns['nameservers'] ?? []) !== []
        ? array_values((array) $recommendedDns['nameservers'])
        : $expectedNs;
    $recommendedIpv4 = array_values((array) ($d['recommended_ipv4'] ?? []));
    $recommendedCname = array_values((array) ($d['recommended_cname'] ?? []));
    $ownershipChallenge = is_array($d['ownership_challenge'] ?? null) ? $d['ownership_challenge'] : null;
    $healthCode = $d['health_code'] ?? 'unchecked';

    $flattenRecord = static function ($record) {
        if (is_array($record)) {
            return $record['value'] ?? $record['name'] ?? json_encode($record);
        }

        return (string) $record;
    };
    $aRecordValue = $recommendedIpv4 !== []
        ? $flattenRecord($recommendedIpv4[0])
        : ($d['recommended_a_default'] ?? '76.76.21.21');
    $cnameValue = $recommendedCname !== []
        ? $flattenRecord($recommendedCname[0])
        : ($d['recommended_cname_default'] ?? 'cname.vercel-dns.com');

    // Reuse the shared DNS record column labels when present.
    $typeLabel = $recommendedDns['record_type_label'] ?? __('domain_dns.record_type');
    $hostLabel = $recommendedDns['record_name_label'] ?? __('domain_dns.record_name');
    $valueLabel = $recommendedDns['record_value_label'] ?? __('domain_dns.record_value');

    // Nothing to instruct when the domain is already healthy.
    $hideForHealthy = in_array($healthCode, ['linked', 'apex_only', 'checks_disabled'], true);
@endphp

@unless ($hideForHealthy)
<div class="domain-connect-methods mt-3 mb-3">
    <h6 class="mb-1">{{ __('domain_diagnostics.methods_title') }}</h6>
    <p class="small text-muted mb-3" dir="auto">{{ __('domain_diagnostics.methods_intro') }}</p>

    {{-- Method 1: nameserver delegation (simplest, but takes over the whole zone) --}}
    <div class="card mb-3 domain-connect-method">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                <h6 class="mb-0" dir="auto"><i class="fas fa-server mr-1"></i>{{ __('domain_diagnostics.method_ns_title') }}</h6>
                <span class="badge badge-primary">{{ __('domain_diagnostics.method_ns_tag') }}</span>
            </div>
            <p class="small text-muted mb-2" dir="auto">{{ __('domain_diagnostics.method_ns_desc') }}</p>
            <ol class="small pl-3 mb-2" dir="auto">
                <li>{{ __('domain_diagnostics.method_ns_step1') }}</li>
                <li>{{ __('domain_diagnostics.method_ns_step2') }}</li>
                <li>{{ __('domain_diagnostics.method_ns_step3') }}</li>
                <li>{{ __('domain_diagnostics.method_ns_step4') }}</li>
            </ol>
            @if ($methodNs !== [])
                <table class="table table-sm table-bordered mb-2">
                    <thead>
                        <tr>
                            <th class="small">{{ $typeLabel }}</th>
                            <th class="small">{{ $valueLabel }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($methodNs as $ns)
                            <tr>
                                <td><code>NS</code></td>
                                <td><bdi dir="ltr"><code>{{ $ns }}</code></bdi></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="alert alert-warning text-dark small mb-0" dir="auto">
                <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('domain_diagnostics.method_ns_email_warning') }}
            </div>
        </div>
    </div>

    {{-- Method 2: add DNS records only, keeping the existing zone (and email) intact --}}
    <div class="card mb-0 domain-connect-method">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                <h6 class="mb-0" dir="auto"><i class="fas fa-list mr-1"></i>{{ __('domain_diagnostics.method_records_title') }}</h6>
                <span class="badge badge-success">{{ __('domain_diagnostics.method_records_tag') }}</span>
            </div>
            <p class="small text-muted mb-2" dir="auto">{{ __('domain_diagnostics.method_records_desc') }}</p>
            <ol class="small pl-3 mb-2" dir="auto">
                <li>{{ __('domain_diagnostics.method_records_step1') }}</li>
                <li>{{ __('domain_diagnostics.method_records_step2') }}</li>
                <li>{{ __('domain_diagnostics.method_records_step3') }}</li>
                <li>{{ __('domain_diagnostics.method_records_step4') }}</li>
                <li>{{ __('domain_diagnostics.method_records_step5') }}</li>
            </ol>
            <table class="table table-sm table-bordered mb-2">
                <thead>
                    <tr>
                        <th class="small">{{ $typeLabel }}</th>
                        <th class="small">{{ $hostLabel }}</th>
                        <th class="small">{{ $valueLabel }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>A</code></td>
                        <td><code>{{ '@' }}</code></td>
                        <td><bdi dir="ltr"><code>{{ $aRecordValue }}</code></bdi></td>
                    </tr>
                    <tr>
                        <td><code>CNAME</code></td>
                        <td><code>www</code></td>
                        <td><bdi dir="ltr"><code>{{ $cnameValue }}</code></bdi></td>
                    </tr>
                    @if ($ownershipChallenge !== null && $ownershipChallenge !== [])
                        <tr>
                            <td><code>{{ strtoupper($ownershipChallenge['type'] ?? 'TXT') }}</code></td>
                            <td><bdi dir="ltr"><code>{{ $ownershipChallenge['domain'] ?? '_vercel' }}</code></bdi></td>
                            <td><bdi dir="ltr"><code class="text-break">{{ $ownershipChallenge['value'] ?? '—' }}</code></bdi></td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <p class="small text-success mb-0" dir="auto"><i class="fas fa-check-circle mr-1"></i>{{ __('domain_diagnostics.method_records_note') }}</p>
        </div>
    </div>
</div>
@endunless
