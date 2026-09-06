<div class="col-md-12">
    @php
        $isFull = ($vercelCapacity['has_cap'] ?? false)
            && ($vercelCapacity['free_entries'] ?? null) === 0;
    @endphp
    <div class="card card-round mb-3 domain-capacity-context {{ $isFull ? 'domain-capacity-context--full' : '' }}">
        <div class="card-body py-3">
            @if ($vercelCapacity['has_cap'] ?? false)
                <div class="progress mb-3" style="height: 8px; border-radius: 4px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $capPercent }}%; background-color: {{ $capHex }};"
                         aria-valuenow="{{ $capPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            @endif
            <p class="text-muted mb-2 small">
                {{ __('vercel_capacity.apex_only_hint') }}
            </p>
            <p class="text-muted mb-0 small">
                <bdi dir="ltr">{{ __('vercel_capacity.breakdown', [
                    'platform' => $vercelCapacity['platform_entries'],
                    'customer_apex' => $vercelCapacity['customer_apex'],
                    'www_redirects' => $vercelCapacity['www_redirects'],
                    'used' => $vercelCapacity['entries_used'],
                    'total' => $vercelCapacity['entries_total'] ?? '—',
                    'free' => $vercelCapacity['free_entries'] ?? '—',
                ]) }}</bdi>
            </p>
            @if ($inventoryUnreliable ?? false)
                <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                    {{ __('vercel_capacity.inventory_unreliable') }}
                </div>
            @elseif ($isFull)
                <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">
                    {{ __('vercel_capacity.at_limit') }}
                </div>
            @endif
            @if ($dbDomainCount !== null && $dbDomainCount !== $vercelCustomerCount)
                <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                    <strong>{{ __('vercel_capacity.db_mismatch_title') }}</strong>
                    {{ __('vercel_capacity.db_mismatch_body', ['db' => $dbDomainCount, 'vercel' => $vercelCustomerCount]) }}
                    <details class="mt-2 domain-capacity-ops">
                        <summary class="cursor-pointer">{{ __('vercel_capacity.ops_title') }}</summary>
                        <ul class="mb-0 mt-2 pl-3">
                            <li class="mb-1"><code>php artisan domains:reconcile-vercel</code> — {{ __('vercel_capacity.reconcile_hint') }}</li>
                            <li><code>php artisan domains:sync-vercel-status</code> — {{ __('vercel_capacity.sync_hint') }}</li>
                        </ul>
                    </details>
                </div>
            @endif
            @if ($vercelCapacity['is_lower_bound'])
                <p class="text-muted small mt-2 mb-0">{{ __('vercel_capacity.lower_bound_hint') }}</p>
            @endif
        </div>
    </div>
</div>
