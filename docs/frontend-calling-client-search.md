# Calling History: Client Search Frontend Handoff

## Endpoint

Use the existing endpoint:

```text
GET /api/v1/calling/calls
```

The endpoint still requires the authenticated Sanctum session/token and the `calling.view_history` permission.

## New query parameters

| Parameter | Type | Behavior |
|---|---|---|
| `client_name` | string | Partial, case-insensitive database search against the client name. |
| `client_number` | string | Partial search against the client phone number. |

Both parameters are optional. If both are supplied, the result must match both filters.

Examples:

```text
/api/v1/calling/calls?client_name=Ahmed
/api/v1/calling/calls?client_number=0501234567
/api/v1/calling/calls?client_name=Ahmed&client_number=0501234567
```

## Existing parameters

The existing filters remain supported:

```text
status
direction
start_date
end_date
per_page
```

Example combined request:

```text
/api/v1/calling/calls?client_name=Ahmed&direction=outbound&per_page=20
```

## Frontend implementation guidance

1. Add a search input for client name.
2. Add a search input for client phone number.
3. Send the values as `client_name` and `client_number` in the request query string.
4. Trim whitespace and omit empty values.
5. Reset pagination to page 1 whenever either search value changes.
6. Preserve the existing `status`, `direction`, date, and `per_page` parameters.
7. Debounce typing before requesting the API, preferably around 300–500 ms.
8. Keep rendering the existing paginated response and `customer` fields; the response shape has not changed.

## Authorization and visibility

The backend continues to enforce visibility:

- Users with `calling.view_all_history` search across all calls for their tenant.
- Other users search only their own calls.
- Results remain restricted to the authenticated user's tenant.

The frontend must not attempt to implement these restrictions locally.

## Backend verification

The following requests can be used after logging in as user `kkkkk`:

```text
GET /api/v1/calling/calls?client_name=Ahmed
GET /api/v1/calling/calls?client_number=1430
```

Demo data can be recreated in a staging environment with:

```bash
php artisan db:seed --class=CallingDemoDataForKkkkSeeder --force
```
