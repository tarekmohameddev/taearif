@php
    $plan = is_array($d['dns_action_plan'] ?? null) ? $d['dns_action_plan'] : [];
    $planMode = $plan['mode'] ?? ($d['dns_mode'] ?? 'vercel_ns');
    $planRows = is_array($plan['rows'] ?? null) ? $plan['rows'] : [];
    $planNameservers = is_array($plan['nameservers'] ?? null) ? $plan['nameservers'] : [];
    $actionClasses = [
        'add' => 'success',
        'delete' => 'danger',
        'replace' => 'warning',
        'ensure' => 'info',
        'keep' => 'secondary',
    ];
    $copyRows = collect($planRows)->filter(fn ($row) => !empty($row['type']) && !empty($row['host']) && !empty($row['value']))->values();
@endphp

@if ($plan !== [])
    <div class="card border-primary mb-3 domain-registrar-plan">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h6 class="mb-1"><i class="fas fa-tasks mr-1"></i>{{ __('domain_diagnostics.registrar_plan_title') }}</h6>
                <span class="badge badge-light text-primary mb-1">{{ __('domain_diagnostics.registrar_plan_only_badge') }}</span>
            </div>
            <p class="small text-muted mb-0" dir="auto">{{ __('domain_diagnostics.registrar_plan_intro') }}</p>
        </div>
        <div class="card-body p-3">
            @if ($planMode === 'external_dns')
                <div class="alert alert-primary py-2 font-weight-bold" dir="auto">
                    <i class="fas fa-check-circle mr-1"></i>{{ __('domain_diagnostics.registrar_plan_only_instruction') }}
                </div>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-1 copy-dns-all"
                            data-copy-success="{{ __('domain_diagnostics.copy_success') }}"
                            data-copy-failure="{{ __('domain_diagnostics.copy_failure') }}">
                        <i class="fas fa-copy mr-1"></i>{{ __('domain_diagnostics.copy_all_records') }}
                    </button>
                    <span class="small text-muted mb-1">{{ __('domain_diagnostics.copy_all_hint') }}</span>
                </div>
                <div class="alert alert-info py-2" dir="auto">
                    <strong>{{ __('domain_diagnostics.registrar_where_title') }}:</strong>
                    {{ __('domain_diagnostics.registrar_where_external') }}
                </div>
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body py-2 px-3">
                        <label class="small font-weight-bold mb-1" for="dnsProviderGuide-{{ $domain->id ?? 'domain' }}">{{ __('domain_diagnostics.provider_guide_title') }}</label>
                        <select id="dnsProviderGuide-{{ $domain->id ?? 'domain' }}" class="form-control form-control-sm dns-provider-guide-select">
                            <option value="saudinic">{{ __('domain_diagnostics.provider_saudinic') }}</option>
                            <option value="cloudflare">{{ __('domain_diagnostics.provider_cloudflare') }}</option>
                            <option value="generic">{{ __('domain_diagnostics.provider_other') }}</option>
                        </select>
                        <p class="small text-muted mb-0 mt-2 dns-provider-guide-text" data-provider="saudinic">{{ __('domain_diagnostics.provider_saudinic_steps') }}</p>
                        <p class="small text-muted mb-0 mt-2 dns-provider-guide-text d-none" data-provider="cloudflare">{{ __('domain_diagnostics.provider_cloudflare_steps') }}</p>
                        <p class="small text-muted mb-0 mt-2 dns-provider-guide-text d-none" data-provider="generic">{{ __('domain_diagnostics.provider_other_steps') }}</p>
                    </div>
                </div>
                @if ($planRows !== [] && collect($planRows)->contains(fn ($row) => ($row['host'] ?? '') === 'www' && ($row['action'] ?? '') === 'delete'))
                    <div class="alert alert-warning text-dark py-2" dir="auto">
                        <i class="fas fa-exclamation-triangle mr-1"></i><strong>{{ __('domain_diagnostics.www_conflict_title') }}</strong>
                        {{ __('domain_diagnostics.www_conflict_body') }}
                    </div>
                @endif

                <div class="alert alert-success py-2" dir="auto">
                    <strong>{{ __('domain_diagnostics.registrar_nameservers_action') }}:</strong>
                    {{ __('domain_diagnostics.registrar_keep_nameservers') }}
                    @if ($planNameservers !== [])
                        <span class="d-block mt-1" dir="ltr">
                            @foreach ($planNameservers as $nameserver)
                                <code class="mr-2">{{ $nameserver }}</code>
                            @endforeach
                        </span>
                    @endif
                </div>

                @if (! ($plan['lookup_complete'] ?? false))
                    <div class="alert alert-warning py-2" dir="auto">
                        {{ __('domain_diagnostics.registrar_lookup_incomplete') }}
                    </div>
                @endif

                @if ($planRows !== [])
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2 domain-registrar-plan-table">
                            <thead>
                                <tr>
                                    <th>{{ __('domain_diagnostics.registrar_col_action') }}</th>
                                    <th>{{ __('domain_dns.record_type') }}</th>
                                    <th>{{ __('domain_diagnostics.registrar_col_host') }}</th>
                                    <th>{{ __('domain_dns.record_value') }}</th>
                                    <th>{{ __('domain_diagnostics.registrar_col_ttl') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($planRows as $row)
                                    @php
                                        $action = $row['action'] ?? 'ensure';
                                        $badgeClass = $actionClasses[$action] ?? 'secondary';
                                        $rowClass = in_array($action, ['add', 'replace'], true)
                                            ? 'table-success'
                                            : ($action === 'delete' ? 'table-danger' : '');
                                    @endphp
                                    <tr class="{{ $rowClass }} dns-plan-row" data-copy-line="{{ $row['action'] === 'delete' ? __('domain_diagnostics.copy_delete_prefix') : __('domain_diagnostics.copy_record_prefix') }} {{ $row['type'] }} {{ $row['host'] }} {{ $row['value'] }}">
                                        <td>
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ __("domain_diagnostics.registrar_action.{$action}") }}
                                            </span>
                                        </td>
                                        <td><code dir="ltr">{{ $row['type'] ?? '—' }}</code></td>
                                        <td><code dir="ltr">{{ $row['host'] ?? '—' }}</code></td>
                                        <td>
                                            <div class="d-flex align-items-start justify-content-between">
                                                <code class="text-break mr-2" dir="ltr">{{ $row['value'] ?? '—' }}</code>
                                                <button type="button" class="btn btn-link btn-sm p-0 copy-dns-value flex-shrink-0"
                                                        title="{{ __('domain_diagnostics.copy_value') }}"
                                                        aria-label="{{ __('domain_diagnostics.copy_value') }}"
                                                        data-copy-value="{{ $row['value'] ?? '' }}"
                                                        data-copy-success="{{ __('domain_diagnostics.copy_success') }}"
                                                        data-copy-failure="{{ __('domain_diagnostics.copy_failure') }}">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>{{ ($row['ttl'] ?? 'auto') === 'auto' ? __('domain_diagnostics.registrar_ttl_auto') : $row['ttl'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-success small mb-2">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('domain_diagnostics.registrar_no_record_action') }}
                    </p>
                @endif

                @if (($plan['kept_existing_ipv4_group'] ?? false) || ($plan['kept_existing_cname_group'] ?? false))
                    <p class="small text-muted mb-2" dir="auto">
                        {{ __('domain_diagnostics.registrar_existing_group_kept') }}
                    </p>
                @endif

                <p class="small text-muted mb-0" dir="auto">{{ __('domain_diagnostics.registrar_preserve_other_records') }}</p>
            @else
                <div class="alert alert-info py-2" dir="auto">
                    <strong>{{ __('domain_diagnostics.registrar_where_title') }}:</strong>
                    {{ __('domain_diagnostics.registrar_where_nameservers') }}
                </div>
                <p class="small mb-2" dir="auto">
                    {{ ($plan['nameserver_action'] ?? 'replace') === 'keep'
                        ? __('domain_diagnostics.registrar_nameservers_already_correct')
                        : __('domain_diagnostics.registrar_replace_nameservers') }}
                </p>
                <table class="table table-sm table-bordered mb-2">
                    <thead>
                        <tr>
                            <th>{{ __('domain_dns.record_type') }}</th>
                            <th>{{ __('domain_dns.record_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($planNameservers as $nameserver)
                            <tr>
                                <td><code>NS</code></td>
                                <td><code dir="ltr">{{ $nameserver }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="alert alert-danger py-2 mb-0" dir="auto">
                    {{ __('domain_diagnostics.registrar_nameserver_migration_warning') }}
                </div>
            @endif

            <p class="small font-weight-bold mt-3 mb-0" dir="auto">
                {{ __('domain_diagnostics.registrar_finish') }}
            </p>
        </div>
    </div>
@endif
