@if (config('services.posthog.enabled') && config('services.posthog.key'))
  @php
    $posthogHost = rtrim((string) config('services.posthog.host'), '/');
    $posthogKey = config('services.posthog.key');
    $identity = $posthogIdentity ?? null;
  @endphp
  <script src="{{ $posthogHost }}/static/array.js"></script>
  <script>
    posthog.init(@json($posthogKey), {
      api_host: @json($posthogHost),
      person_profiles: 'identified_only',
      capture_pageview: true,
      autocapture: true,
    });
    @if (!empty($identity['distinct_id']))
      posthog.identify(@json($identity['distinct_id']), @json($identity['properties'] ?? []));
      @foreach ($identity['groups'] ?? [] as $group)
        posthog.group(@json($group['type']), @json($group['key']), @json($group['properties'] ?? []));
      @endforeach
    @endif
  </script>
@endif
