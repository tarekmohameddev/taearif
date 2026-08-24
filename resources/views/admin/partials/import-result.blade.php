@php
    $sheetLabels = [
        'properties' => [
            'title' => 'العقارات',
            'plural' => 'عقارات',
            'singular' => 'عقار',
            'new_suffix' => 'جديدة',
        ],
        'projects' => [
            'title' => 'المشاريع',
            'plural' => 'مشاريع',
            'singular' => 'مشروع',
            'new_suffix' => 'جديدة',
        ],
        'customers' => [
            'title' => 'العملاء',
            'plural' => 'عملاء',
            'singular' => 'عميل',
            'new_suffix' => 'جدد',
        ],
        'requests' => [
            'title' => 'طلبات العقار',
            'plural' => 'طلبات',
            'singular' => 'طلب',
            'new_suffix' => 'جديدة',
        ],
        'crm_settings' => [
            'title' => 'إعدادات CRM',
            'plural' => 'إعدادات',
            'singular' => 'إعداد',
            'new_suffix' => 'جديدة',
        ],
    ];

    $nounFor = function (int $count, array $meta): string {
        return $count === 1 ? $meta['singular'] : $meta['plural'];
    };
@endphp

@if (!empty($importResult) && is_array($importResult))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>{{ __('نتيجة الاستيراد') }}</strong>

        @foreach ($importResult as $sheet => $r)
            @if ($sheet === 'note')
                <div class="mt-2"><em>{{ is_string($r) ? $r : json_encode($r) }}</em></div>
                @continue
            @endif

            @if (!is_array($r))
                <div class="mt-2"><em>{{ $r }}</em></div>
                @continue
            @endif

            @php
                $meta = $sheetLabels[$sheet] ?? [
                    'title' => $sheet,
                    'plural' => $sheet,
                    'singular' => $sheet,
                    'new_suffix' => 'جديدة',
                ];
                $imported = (int) ($r['imported'] ?? 0);
                $updated = (int) ($r['updated'] ?? 0);
                $skipped = (int) ($r['skipped'] ?? 0);
                $errors = $r['errors'] ?? [];
                $warnings = $r['warnings'] ?? [];
                $parts = [];

                if ($imported > 0) {
                    $parts[] = 'تم إنشاء ' . $imported . ' ' . $nounFor($imported, $meta) . ' ' . $meta['new_suffix'];
                }
                if ($updated > 0) {
                    $parts[] = 'تم تحديث ' . $updated . ' ' . $nounFor($updated, $meta);
                }
                if ($skipped > 0) {
                    $parts[] = 'تم تخطي ' . $skipped . ' ' . $nounFor($skipped, $meta);
                }

                if (empty($parts)) {
                    $summaryLine = 'لا توجد سجلات مستوردة.';
                } else {
                    $summaryLine = implode('،' . "\n", $parts) . '.';
                }

                if (empty($errors) && empty($warnings)) {
                    $summaryLine .= ' لا أخطاء.';
                }
            @endphp

            <div class="mt-3">
                <strong>{{ $meta['title'] }}:</strong>
                <div style="white-space: pre-line;">{{ $summaryLine }}</div>

                @if (!empty($errors))
                    <div class="mt-1 text-danger">
                        <strong>الأخطاء:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($errors as $err)
                                <li>
                                    @if (!empty($err['row']))
                                        صف {{ $err['row'] }}:
                                    @endif
                                    {{ $err['message'] ?? (is_string($err) ? $err : json_encode($err)) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($warnings))
                    <div class="mt-1 text-warning">
                        <strong>التحذيرات:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($warnings as $warn)
                                <li>
                                    @if (!empty($warn['row']))
                                        صف {{ $warn['row'] }}:
                                    @endif
                                    {{ $warn['message'] ?? (is_string($warn) ? $warn : json_encode($warn)) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach

        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
