@php
    $excluded = $card['excludedByCurrency'] ?? [];
    $hasWarning = ($card['unpricedRecords'] ?? 0) > 0 || ($card['excludedNonSarRecords'] ?? 0) > 0;
    $formatMoneyAmount = static function ($amount): string {
        return number_format((float) ($amount ?? 0), 0);
    };
@endphp

<article class="card dashboard-panel dashboard-money-card dashboard-tone-{{ $card['tone'] ?? 'neutral' }} w-100" @if(!empty($card['tooltip'])) title="{{ $card['tooltip'] }}" @endif>
    <div class="card-body dashboard-money-body">
        <div class="dashboard-money-header">
            <div class="dashboard-money-copy">
                <p class="dashboard-kpi-label">{{ $card['label'] }}</p>
                <strong class="dashboard-money-value">
                    <span class="dashboard-money-amount">{{ $formatMoneyAmount($card['amount'] ?? 0) }}</span>
                    <span class="dashboard-money-currency">{{ $card['currency'] ?? 'SAR' }}</span>
                </strong>
                <p class="dashboard-kpi-helper">{{ $card['helper'] }}</p>
            </div>
            <span class="dashboard-money-icon dashboard-tone-{{ $card['tone'] ?? 'neutral' }}">
                <i data-lucide="{{ $card['icon'] ?? 'banknote' }}"></i>
            </span>
        </div>

        <div class="dashboard-money-meta">
            <span>{{ number_format((int) ($card['pricedRecords'] ?? 0)) }} {{ __('priced records') }}</span>
            <span>{{ __('As of') }} {{ $card['asOf']->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }}</span>
        </div>

        @if($hasWarning)
            <div class="dashboard-money-warning">
                @if(($card['unpricedRecords'] ?? 0) > 0)
                    <p>{{ __(':count eligible records were excluded because they are missing a positive amount.', ['count' => number_format((int) $card['unpricedRecords'])]) }}</p>
                @endif
                @if(($card['excludedNonSarRecords'] ?? 0) > 0)
                    <p>{{ __(':count non-SAR records were excluded from the SAR total.', ['count' => number_format((int) $card['excludedNonSarRecords'])]) }}</p>
                @endif
                @foreach($excluded as $row)
                    <p>{{ __('Excluded :currency :amount across :count record(s)', ['currency' => $row['currency'], 'amount' => $formatMoneyAmount($row['amount'] ?? 0), 'count' => number_format((int) $row['records'])]) }}</p>
                @endforeach
            </div>
        @endif
    </div>
</article>
