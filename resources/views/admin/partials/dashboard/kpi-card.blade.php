@php
    $isLinked = !empty($card['url']);
    $classes = 'card dashboard-kpi dashboard-kpi-' . ($size ?? 'compact')
        . ' dashboard-tone-' . ($card['tone'] ?? 'neutral')
        . ($isLinked ? ' dashboard-kpi-linked' : '');
    $attributes = collect($card['attributes'] ?? [])
        ->map(fn ($value, $key) => sprintf('%s="%s"', e((string) $key), e((string) $value)))
        ->implode(' ');
    $displayValue = array_key_exists('displayValue', $card)
        ? (string) $card['displayValue']
        : number_format((int) $card['value']);
@endphp

@if($isLinked)
    <a class="{{ $classes }} w-100" href="{{ $card['url'] }}" aria-label="{{ $card['label'] }}" {!! $attributes !!}>
@else
    <article class="{{ $classes }} w-100" {!! $attributes !!}>
@endif
        <div class="card-body">
            <div class="dashboard-kpi-topline">
                <span class="dashboard-kpi-icon"><i data-lucide="{{ $card['icon'] }}"></i></span>
                @if($isLinked)
                    <i class="dashboard-kpi-arrow" data-lucide="arrow-up-right"></i>
                @endif
            </div>
            <div class="dashboard-kpi-copy">
                <p class="dashboard-kpi-label">{{ $card['label'] }}</p>
                <strong class="dashboard-kpi-value">{{ $displayValue }}</strong>
            </div>
            <p class="dashboard-kpi-helper">{{ $card['helper'] }}</p>
        </div>
@if($isLinked)
    </a>
@else
    </article>
@endif
