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
@endphp

@if ($plan !== [])
    <div class="card border-primary mb-3 domain-registrar-plan">
        <div class="card-header bg-light">
            <h6 class="mb-1"><i class="fas fa-tasks mr-1"></i>{{ __('domain_diagnostics.registrar_plan_title') }}</h6>
            <p class="small text-muted mb-0" dir="auto">{{ __('domain_diagnostics.registrar_plan_intro') }}</p>
        </div>
        <div class="card-body p-3">
            @if ($planMode === 'external_dns')
                <div class="alert alert-info py-2" dir="auto">
                    <strong>{{ __('domain_diagnostics.registrar_where_title') }}:</strong>
                    {{ __('domain_diagnostics.registrar_where_external') }}
                </div>

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
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ __("domain_diagnostics.registrar_action.{$action}") }}
                                            </span>
                                        </td>
                                        <td><code dir="ltr">{{ $row['type'] ?? '—' }}</code></td>
                                        <td><code dir="ltr">{{ $row['host'] ?? '—' }}</code></td>
                                        <td><code class="text-break" dir="ltr">{{ $row['value'] ?? '—' }}</code></td>
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

                <div class="alert alert-warning text-dark py-2 mb-2" dir="auto">
                    {{ __('domain_diagnostics.registrar_do_not_mix') }}
                </div>
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
