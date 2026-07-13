@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">
        {{ __('Tenant Activity Log') }} &mdash; {{ $tenant->username }}
    </h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{ route('admin.dashboard') }}">
                <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.tenant-activity-logs.index') }}">{{ __('Tenant Activity Logs') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{ $tenant->username }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    {{ __('Save Pages Activity') }} &mdash; {{ __('Tenant') }}: {{ $tenant->username }}
                    @if ($tenant->generalSettings?->site_name)
                        ({{ $tenant->generalSettings->site_name }})
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if ($logs->count() === 0)
                    <h5 class="text-center">{{ __('No activity logs found for this tenant yet') }}</h5>
                @else
                    <div class="accordion" id="activityLogAccordion">
                        @foreach ($logs as $log)
                            @php $panelId = 'log-' . $log->id; @endphp
                            <div class="card mb-2">
                                <div class="card-header p-2" id="heading-{{ $panelId }}">
                                    <button class="btn btn-link w-100 text-left" type="button" data-toggle="collapse" data-target="#collapse-{{ $panelId }}" aria-expanded="false" aria-controls="collapse-{{ $panelId }}">
                                        <strong>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</strong>
                                        &mdash;
                                        {{ __('Login Source') }}: {{ data_get($log->login_session_meta, 'loginSource', 'N/A') }}
                                        &mdash;
                                        {{ __('IP') }}: {{ data_get($log->login_session_meta, 'loginIp', $log->server_ip) }}
                                    </button>
                                </div>
                                <div id="collapse-{{ $panelId }}" class="collapse" aria-labelledby="heading-{{ $panelId }}" data-parent="#activityLogAccordion">
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <h6>{{ __('Login Session Meta') }}</h6>
                                                <pre class="bg-light p-2 rounded small">{{ json_encode($log->login_session_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <h6>{{ __('Changes') }}</h6>
                                                <div class="activity-diff" data-before="{{ json_encode($log->before, JSON_UNESCAPED_UNICODE) }}" data-after="{{ json_encode($log->after, JSON_UNESCAPED_UNICODE) }}"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>{{ __('Before') }}</h6>
                                                <pre class="bg-light p-2 rounded small" style="max-height: 400px; overflow: auto;">{{ json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>{{ __('After') }}</h6>
                                                <pre class="bg-light p-2 rounded small" style="max-height: 400px; overflow: auto;">{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function isPlainObject(v) {
        return v !== null && typeof v === 'object' && !Array.isArray(v);
    }

    function diffValues(before, after, path, rows) {
        var beforeIsObj = isPlainObject(before);
        var afterIsObj = isPlainObject(after);

        if (beforeIsObj && afterIsObj) {
            var keys = Array.from(new Set(Object.keys(before).concat(Object.keys(after))));
            keys.forEach(function (key) {
                diffValues(before[key], after[key], path ? path + '.' + key : key, rows);
            });
            return;
        }

        var beforeStr = JSON.stringify(before);
        var afterStr = JSON.stringify(after);

        if (beforeStr === afterStr) {
            return;
        }

        rows.push({ path: path || '(root)', before: before, after: after });
    }

    function renderDiff(container) {
        var before = {};
        var after = {};
        try { before = JSON.parse(container.getAttribute('data-before') || '{}') || {}; } catch (e) {}
        try { after = JSON.parse(container.getAttribute('data-after') || '{}') || {}; } catch (e) {}

        var rows = [];
        diffValues(before, after, '', rows);

        if (rows.length === 0) {
            container.innerHTML = '<span class="text-muted">{{ __('No differences detected') }}</span>';
            return;
        }

        var table = document.createElement('table');
        table.className = 'table table-sm table-bordered';

        var thead = document.createElement('thead');
        thead.innerHTML = '<tr><th>{{ __('Field') }}</th><th>{{ __('Before') }}</th><th>{{ __('After') }}</th></tr>';
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        rows.forEach(function (row) {
            var tr = document.createElement('tr');

            var tdField = document.createElement('td');
            tdField.textContent = row.path;
            tr.appendChild(tdField);

            var tdBefore = document.createElement('td');
            tdBefore.innerHTML = '<pre class="mb-0 small">' + escapeHtml(formatValue(row.before)) + '</pre>';
            tr.appendChild(tdBefore);

            var tdAfter = document.createElement('td');
            tdAfter.innerHTML = '<pre class="mb-0 small">' + escapeHtml(formatValue(row.after)) + '</pre>';
            tr.appendChild(tdAfter);

            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        container.innerHTML = '';
        container.appendChild(table);
    }

    function formatValue(value) {
        if (value === undefined) {
            return '(none)';
        }
        if (typeof value === 'string') {
            return value;
        }
        return JSON.stringify(value, null, 2);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.activity-diff').forEach(renderDiff);
    });
})();
</script>
@endsection
