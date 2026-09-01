<div class="col-md-12">
    <div class="card card-round mb-3 domain-capacity-context {{ $vercelCapacity['customer_domains_remaining'] === 0 ? 'domain-capacity-context--full' : '' }}">
        <div class="card-body py-3">
            <div class="progress mb-3" style="height: 8px; border-radius: 4px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ $capPercent }}%; background-color: {{ $capHex }};"
                     aria-valuenow="{{ $capPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p class="text-muted mb-2 small">
                {{ __('Each customer domain uses two Vercel entries (apex and www).') }}
            </p>
            <p class="text-muted mb-0 small">
                <bdi dir="ltr">{{ __('vercel_capacity.breakdown', [
                    'platform' => $vercelCapacity['platform_entries'],
                    'customer_entries' => $vercelCapacity['customer_entries_used'],
                    'customer_domains' => $vercelCapacity['customer_domains_in_use'],
                    'used' => $vercelCapacity['entries_used'],
                    'total' => $vercelCapacity['entries_total'],
                ]) }}</bdi>
            </p>
            @if ($vercelCapacity['customer_domains_remaining'] === 0)
                <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small">
                    {{ __('The project is at its limit — new customer domains will fail until capacity is increased.') }}
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
                <p class="text-muted small mt-2 mb-0">{{ __('Count capped at 1,000 entries; the real total may be higher.') }}</p>
            @endif
        </div>
    </div>
</div>
