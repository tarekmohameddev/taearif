@php
    $isLinked = !empty($card['url']);
    $classes = 'card dashboard-kpi dashboard-kpi-' . ($size ?? 'compact')
        . ' dashboard-tone-' . ($card['tone'] ?? 'neutral')
        . ($isLinked ? ' dashboard-kpi-linked' : '');
@endphp

@if($isLinked)
    <a class="{{ $classes }} w-100" href="{{ $card['url'] }}" aria-label="{{ $card['label'] }}">
@else
    <article class="{{ $classes }} w-100">
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
                <strong class="dashboard-kpi-value">{{ number_format((int) $card['value']) }}</strong>
            </div>
            <p class="dashboard-kpi-helper">{{ $card['helper'] }}</p>
        </div>
@if($isLinked)
    </a>
@else
    </article>
@endif
