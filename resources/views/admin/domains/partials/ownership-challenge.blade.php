@php
    $challenge = is_array($ownershipChallenge ?? null) ? $ownershipChallenge : null;
    $recommendedDns = $recommendedDns ?? \App\Models\Api\ApiDomainSetting::nameserverInstructions();
    $compact = $compact ?? false;
    $showClaim = $showClaim ?? false;
    $domainId = $domainId ?? null;
@endphp
@if ($challenge !== null && $challenge !== [])
    <div class="domain-ownership-challenge {{ $compact ? 'domain-ownership-challenge--compact' : '' }}">
        <p class="small text-muted mb-2" dir="auto">{{ $recommendedDns['ownership_txt_instruction'] ?? __('domain_dns.ownership_txt_instruction') }}</p>
        <table class="table table-sm table-bordered mb-2">
            <tbody>
                <tr>
                    <th class="small">{{ $recommendedDns['record_type_label'] ?? __('domain_dns.record_type') }}</th>
                    <td>
                        <code>{{ $challenge['type'] ?? 'txt' }}</code>
                        <button type="button"
                                class="btn btn-outline-secondary btn-xs domain-copy-btn ml-1"
                                data-copy="{{ $challenge['type'] ?? 'txt' }}"
                                title="{{ __('domain_admin.copy') }}">{{ __('domain_admin.copy') }}</button>
                    </td>
                </tr>
                <tr>
                    <th class="small">{{ $recommendedDns['record_name_label'] ?? __('domain_dns.record_name') }}</th>
                    <td>
                        <code class="text-break">{{ $challenge['domain'] ?? '—' }}</code>
                        @if (! empty($challenge['domain']))
                            <button type="button"
                                    class="btn btn-outline-secondary btn-xs domain-copy-btn ml-1"
                                    data-copy="{{ $challenge['domain'] }}"
                                    title="{{ __('domain_admin.copy') }}">{{ __('domain_admin.copy') }}</button>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="small">{{ $recommendedDns['record_value_label'] ?? __('domain_dns.record_value') }}</th>
                    <td>
                        <code class="text-break">{{ $challenge['value'] ?? '—' }}</code>
                        @if (! empty($challenge['value']))
                            <button type="button"
                                    class="btn btn-outline-secondary btn-xs domain-copy-btn ml-1"
                                    data-copy="{{ $challenge['value'] }}"
                                    title="{{ __('domain_admin.copy') }}">{{ __('domain_admin.copy') }}</button>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
        @if ($showClaim && $domainId !== null)
            <form class="domain-action-form d-inline-block" action="{{ route('admin.custom-domain.claim-ownership') }}" method="POST">
                @csrf
                <input type="hidden" name="domain_id" value="{{ $domainId }}">
                <button type="submit"
                        class="btn btn-warning btn-sm"
                        title="{{ __('domain_admin.claim_ownership') }}">{{ __('domain_admin.claim_ownership') }}</button>
            </form>
        @endif
    </div>
@endif
