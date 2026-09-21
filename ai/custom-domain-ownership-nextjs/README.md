# Custom Domain Ownership Verification: Next.js Frontend Guide

This guide is the implementation contract for the tenant user dashboard. It explains how a Next.js frontend should display Vercel ownership TXT records returned by the Taearif API and let the authenticated tenant retry verification.

It is intentionally suitable for both a frontend developer and an AI coding agent.

## Scope

Implement this in the authenticated user dashboard's custom-domain settings screen.

The frontend must:

- show ownership verification only when `ownershipVerification.required === true`;
- support one record or separate apex and `www` records;
- guide the user through multiple records sequentially;
- copy the API-provided TXT name and value exactly;
- submit the existing Verify Domain API action and consume its response even when it returns HTTP `422`;
- preserve the current domain-management UI and authentication flow.

The frontend must not:

- call Vercel directly;
- change DNS automatically;
- expose ownership records on public tenant website pages;
- infer TXT values from the domain name;
- combine multiple TXT values into one string;
- treat every HTTP `422` from verification as an unexpected application failure.

## Authentication and permissions

All endpoints in this document require Laravel Sanctum authentication and the `settings.update` permission.

Use the dashboard's existing authenticated API client. Do not create a second authentication implementation. Depending on the dashboard's current setup, this may use a bearer token or Sanctum cookies.

Base path:

```text
{API_BASE_URL}/api
```

## Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/settings/domain` | List domains and ownership state for every domain |
| `GET` | `/settings/domain/{id}` | Load one domain and its ownership state |
| `POST` | `/settings/domain` | Add a domain; the response may immediately contain ownership records |
| `POST` | `/settings/domain/verify` | Refresh verification and ownership state |
| `POST` | `/settings/domain/www/enable` | Enable `www`; refetch the domain afterward |

The Verify request body is:

```json
{
  "id": 123
}
```

The Verify endpoint can return:

| HTTP status | Meaning | Frontend behavior |
|---|---|---|
| `200` | Domain is active/verified | Update the domain and show success |
| `422` | Verification remains pending or failed | Read and render the response body; do not discard it |
| `401` | User is unauthenticated | Use the dashboard's existing auth handling |
| `403` | User lacks permission | Show the existing permission error UI |
| `404` | Domain is not available to this tenant | Refetch the list or return to the domains screen |
| `429` | Verification was requested too often | Disable retry temporarily and show a rate-limit message |
| `503` | Hosting integration is busy/unavailable | Preserve current instructions and allow a later retry |

`POST /settings/domain/verify` is limited to 10 requests per minute. Verification should normally be user-triggered; do not continuously POST in the background.

## Canonical ownership contract

```ts
export type OwnershipScope = 'apex' | 'www';
export type OwnershipStrategy = 'single' | 'sequential';

export interface OwnershipVerificationRecord {
  scope: OwnershipScope;
  hostname: string;
  type: 'TXT' | string;
  name: string;
  value: string;
  reason?: string;
}

export interface OwnershipVerification {
  required: boolean;
  state: string;
  action: 'add_ownership_txt' | null;
  strategy: OwnershipStrategy | null;
  records: OwnershipVerificationRecord[];
  checkedAt: string | null;
}
```

Example requiring separate apex and `www` verification:

```json
{
  "required": true,
  "state": "ownership_required",
  "action": "add_ownership_txt",
  "strategy": "sequential",
  "records": [
    {
      "scope": "apex",
      "hostname": "example.com",
      "type": "TXT",
      "name": "_vercel.example.com",
      "value": "vc-domain-verify=example.com,apex-token"
    },
    {
      "scope": "www",
      "hostname": "www.example.com",
      "type": "TXT",
      "name": "_vercel.example.com",
      "value": "vc-domain-verify=www.example.com,www-token"
    }
  ],
  "checkedAt": "2026-09-16T04:00:00+00:00"
}
```

When no ownership action is needed:

```json
{
  "required": false,
  "state": "verified",
  "action": null,
  "strategy": null,
  "records": [],
  "checkedAt": "2026-09-16T04:05:00+00:00"
}
```

Rules:

- `required` is the canonical visibility flag.
- `records` is the canonical record list.
- `strategy === "sequential"` means the records must be completed in array order.
- The first record is the current step. Remaining records are upcoming steps.
- A `www` record can still be required when the apex domain is already verified.
- `name` and `value` must be displayed and copied exactly as returned.
- `checkedAt` can be used for a “last checked” label but must not determine whether a record is required.

## Where the field appears

### List domains

`GET /api/settings/domain` returns ownership state inside every domain item:

```json
{
  "domains": [
    {
      "id": 123,
      "custom_name": "example.com",
      "status": "pending",
      "dnsMode": "external_dns",
      "ownershipVerification": {
        "required": true,
        "state": "ownership_required",
        "action": "add_ownership_txt",
        "strategy": "single",
        "records": [
          {
            "scope": "apex",
            "hostname": "example.com",
            "type": "TXT",
            "name": "_vercel.example.com",
            "value": "vc-domain-verify=example.com,apex-token"
          }
        ],
        "checkedAt": null
      }
    }
  ]
}
```

### Get one domain

`GET /api/settings/domain/{id}` returns it at the response root:

```text
response.ownershipVerification
```

### Add a domain

`POST /api/settings/domain` returns it in both locations:

```text
response.ownershipVerification
response.data.ownershipVerification
```

Prefer the root field. The nested field is also present so a newly created domain object is self-contained.

### Verify a domain

`POST /api/settings/domain/verify` returns it at the response root for active, pending, and failed outcomes:

```text
response.ownershipVerification
```

Always parse the response JSON for both HTTP `200` and HTTP `422`.

## Backward compatibility

The backend retains the older singular ownership record under external-DNS instructions:

```text
dnsInstructions.ownership.record
```

New clients should use:

```text
ownershipVerification.records
```

The API may also return the plural records under:

```text
dnsInstructions.ownership.records
```

Use the following adapter if the Next.js dashboard can be deployed before or independently from the backend change:

```ts
type JsonRecord = Record<string, unknown>;

const EMPTY_OWNERSHIP: OwnershipVerification = {
  required: false,
  state: 'unchecked',
  action: null,
  strategy: null,
  records: [],
  checkedAt: null,
};

function asObject(value: unknown): JsonRecord | null {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    ? (value as JsonRecord)
    : null;
}

function normalizeOwnership(payload: unknown): OwnershipVerification {
  const root = asObject(payload);
  if (!root) return EMPTY_OWNERSHIP;

  const data = asObject(root.data);
  const canonical = asObject(root.ownershipVerification)
    ?? asObject(data?.ownershipVerification);

  if (canonical) {
    const records = Array.isArray(canonical.records)
      ? (canonical.records as OwnershipVerificationRecord[])
      : [];

    return {
      required: canonical.required === true || records.length > 0,
      state: typeof canonical.state === 'string' ? canonical.state : 'unchecked',
      action: canonical.action === 'add_ownership_txt' ? canonical.action : null,
      strategy:
        canonical.strategy === 'sequential' || canonical.strategy === 'single'
          ? canonical.strategy
          : records.length > 1
            ? 'sequential'
            : records.length === 1
              ? 'single'
              : null,
      records,
      checkedAt: typeof canonical.checkedAt === 'string' ? canonical.checkedAt : null,
    };
  }

  const dnsInstructions = asObject(root.dnsInstructions)
    ?? asObject(data?.dnsInstructions);
  const legacyOwnership = asObject(dnsInstructions?.ownership);
  const plural = Array.isArray(legacyOwnership?.records)
    ? (legacyOwnership.records as OwnershipVerificationRecord[])
    : [];
  const singular = asObject(legacyOwnership?.record);
  const records = plural.length > 0
    ? plural
    : singular
      ? [{
          scope: 'apex',
          hostname: '',
          type: String(singular.type ?? 'TXT'),
          name: String(singular.name ?? ''),
          value: String(singular.value ?? ''),
        }]
      : [];

  return {
    required: records.length > 0,
    state: records.length > 0 ? 'ownership_required' : 'unchecked',
    action: records.length > 0 ? 'add_ownership_txt' : null,
    strategy: records.length > 1 ? 'sequential' : records.length === 1 ? 'single' : null,
    records,
    checkedAt: null,
  };
}
```

Do not make the legacy fallback the primary implementation. It cannot reliably describe separate apex and `www` steps on older backend responses.

## Recommended UI

Render the ownership panel inside each domain card or domain details panel, near the current DNS instructions and Verify button.

### Required state

When `ownership.required` is true and at least one record exists, show:

1. Warning title: “Domain ownership verification required”.
2. Explanation that the TXT record must be added at the user's DNS provider.
3. Current step label: “Main domain” for `apex`, or “www domain” for `www`.
4. Hostname.
5. Record type, name, and value in left-to-right text.
6. Separate copy buttons for name and value.
7. Sequential progress when there is more than one record.
8. “I added the TXT record — Verify again” button.
9. Last checked time when available.

For a sequential response, emphasize only `records[0]` as the current action. The remaining records may be shown as upcoming steps. After verification, use the new API response to determine which record is now current.

### Not required

When `required` is false:

- do not show the ownership warning or TXT values;
- continue rendering the dashboard's existing domain status, DNS mode, and `www` state;
- optionally show the existing verified state when `state === "verified"`.

### Copy behavior

Use `navigator.clipboard.writeText()` and show short feedback such as “Copied”. Do not alter, trim, quote, URL-encode, or concatenate the value.

DNS providers differ in whether they expect `_vercel` or a fully qualified name. The API returns the record name that should be displayed. The frontend may provide a neutral note that some DNS providers automatically append the root domain, but it must not rewrite the API value.

## Sequential verification flow

When `strategy === "sequential"`:

1. Display the first record as the current step.
2. The user adds that exact TXT record at their DNS provider.
3. The user waits for DNS propagation and clicks Verify again.
4. Send `POST /api/settings/domain/verify` with the domain ID.
5. Replace local domain state with the response.
6. If another record remains, show it as the next current step.
7. If `required` becomes false, hide the ownership panel and show the updated domain state.

Do not tell the user to combine apex and `www` values. They can use the same TXT record name with different values, and the expected Vercel workflow may require verifying the apex first and then replacing it with the `www` value.

## Next.js API function

Adapt this to the dashboard's existing API wrapper. The important behavior is parsing the body for `422` and returning it to the UI.

```ts
export interface VerifyDomainResponse {
  success: boolean;
  message: string;
  outcome?: 'active' | 'pending' | 'failed';
  health?: string;
  retryable?: boolean;
  ownershipVerification?: OwnershipVerification;
  data?: {
    id: number;
    custom_name: string;
    status: string;
    verificationStatus: 'verified' | 'pending' | 'failed';
    message?: string;
  };
}

export async function verifyDomain(
  id: number,
  signal?: AbortSignal,
): Promise<VerifyDomainResponse> {
  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/settings/domain/verify`, {
    method: 'POST',
    credentials: 'include', // Keep or replace according to the existing auth client.
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ id }),
    signal,
  });

  const body = (await response.json()) as VerifyDomainResponse;

  // 422 is a valid domain-verification result and contains refreshed instructions.
  if (response.ok || response.status === 422) {
    return body;
  }

  throw Object.assign(new Error(body.message || 'Unable to verify domain'), {
    status: response.status,
    body,
  });
}
```

If the existing client is Axios-based, configure this request to accept `422`:

```ts
const response = await api.post<VerifyDomainResponse>(
  '/api/settings/domain/verify',
  { id },
  { validateStatus: status => (status >= 200 && status < 300) || status === 422 },
);
```

## Example client component

This is a behavioral example, not a required visual design. Reuse the dashboard's existing Button, Alert, toast, translation, and API components.

```tsx
'use client';

import { useMemo, useState } from 'react';

interface OwnershipPanelProps {
  domainId: number;
  ownership: OwnershipVerification;
  onVerificationResult: (result: VerifyDomainResponse) => void;
}

export function OwnershipPanel({
  domainId,
  ownership,
  onVerificationResult,
}: OwnershipPanelProps) {
  const [isVerifying, setIsVerifying] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const currentRecord = ownership.records[0];
  const upcomingRecords = useMemo(
    () => ownership.records.slice(1),
    [ownership.records],
  );

  if (!ownership.required || !currentRecord) return null;

  async function handleVerify() {
    setIsVerifying(true);
    setError(null);

    try {
      const result = await verifyDomain(domainId);
      onVerificationResult(result);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : 'Unable to verify domain');
    } finally {
      setIsVerifying(false);
    }
  }

  return (
    <section aria-labelledby={`ownership-title-${domainId}`}>
      <h3 id={`ownership-title-${domainId}`}>
        Domain ownership verification required
      </h3>

      <p>Add this TXT record at your DNS provider, then verify again.</p>

      {ownership.strategy === 'sequential' && (
        <p>
          Step 1 of {ownership.records.length}. Complete the records in the order shown.
        </p>
      )}

      <dl>
        <dt>Domain</dt>
        <dd dir="ltr">{currentRecord.hostname}</dd>
        <dt>Type</dt>
        <dd dir="ltr">{currentRecord.type}</dd>
        <dt>Name</dt>
        <dd dir="ltr">
          <code>{currentRecord.name}</code>
          <button
            type="button"
            onClick={() => navigator.clipboard.writeText(currentRecord.name)}
            aria-label="Copy TXT record name"
          >
            Copy
          </button>
        </dd>
        <dt>Value</dt>
        <dd dir="ltr">
          <code>{currentRecord.value}</code>
          <button
            type="button"
            onClick={() => navigator.clipboard.writeText(currentRecord.value)}
            aria-label="Copy TXT record value"
          >
            Copy
          </button>
        </dd>
      </dl>

      {upcomingRecords.length > 0 && (
        <details>
          <summary>Upcoming verification steps</summary>
          <ol>
            {upcomingRecords.map(record => (
              <li key={`${record.scope}-${record.value}`}>
                {record.scope === 'www' ? 'www domain' : 'Main domain'}:{' '}
                <span dir="ltr">{record.hostname}</span>
              </li>
            ))}
          </ol>
        </details>
      )}

      {error && <p role="alert">{error}</p>}

      <button type="button" disabled={isVerifying} onClick={handleVerify}>
        {isVerifying ? 'Checking…' : 'I added the TXT record — Verify again'}
      </button>
    </section>
  );
}
```

When `onVerificationResult` runs, update the cached domain using the new response or invalidate/refetch these queries:

```text
GET /api/settings/domain
GET /api/settings/domain/{id}
```

For TanStack Query, invalidate the list and detail query keys after every Verify result, including `422`.

## Suggested translations

At minimum provide English and Arabic strings for:

| Key | English | Arabic |
|---|---|---|
| `domainOwnership.title` | Domain ownership verification required | مطلوب التحقق من ملكية النطاق |
| `domainOwnership.description` | Add this TXT record at your DNS provider, then verify again. | أضف سجل TXT هذا لدى مزود DNS ثم أعد التحقق. |
| `domainOwnership.apex` | Main domain | النطاق الرئيسي |
| `domainOwnership.www` | www domain | نطاق www |
| `domainOwnership.type` | Type | النوع |
| `domainOwnership.name` | Name | الاسم |
| `domainOwnership.value` | Value | القيمة |
| `domainOwnership.copy` | Copy | نسخ |
| `domainOwnership.copied` | Copied | تم النسخ |
| `domainOwnership.verifyAgain` | I added the TXT record — Verify again | أضفت سجل TXT — تحقق مرة أخرى |
| `domainOwnership.checking` | Checking… | جارٍ التحقق… |
| `domainOwnership.upcoming` | Upcoming verification steps | خطوات التحقق التالية |
| `domainOwnership.sequential` | Complete these records in the order shown. | أكمل هذه السجلات بالترتيب المعروض. |

Keep DNS hostnames, record names, and values in an element with `dir="ltr"`, including inside the Arabic interface.

## State and error handling

- Keep the last successful ownership instructions visible during transient `503` or network errors.
- Disable the Verify button while its request is in flight.
- Prevent duplicate submissions.
- Do not optimistically mark the domain verified.
- Replace the instructions only with a successfully parsed API response.
- Show `response.message` as status information, but drive ownership visibility from `ownershipVerification.required`.
- Do not use only `domain.status` to decide whether the ownership panel is visible. A separate `www` record may be required while the apex is active.
- Treat `checkedAt` as informational and format it in the user's timezone.
- Do not log TXT values to analytics, session replay, or error-monitoring breadcrumbs.

## Test matrix

The frontend implementation should include component and API-client tests for these cases:

1. `required: false`, empty records: ownership panel is hidden.
2. One apex record: apex instructions and copy buttons are shown.
3. One `www` record with an already verified apex: `www` instructions are still shown.
4. Two records with `strategy: sequential`: first is current and second is upcoming.
5. Copy Name copies `name` exactly.
6. Copy Value copies `value` exactly.
7. Verify `200`: UI updates to verified and ownership panel disappears when required becomes false.
8. Verify `422` with ownership records: response is consumed and refreshed instructions remain visible.
9. Verify `429`: retry is temporarily disabled and a useful message is shown.
10. Verify `503` or network error: last known TXT instructions remain visible.
11. Arabic layout: labels are RTL while DNS values remain LTR.
12. Legacy response containing only `dnsInstructions.ownership.record`: one fallback record is rendered.
13. Response changes from apex record to `www` record: the current step updates without stale apex data.

## Acceptance criteria

- The ownership panel appears for any domain whose canonical response has `required: true`.
- Both apex and `www` scopes are supported.
- Multiple records are presented sequentially, not concatenated.
- The user can copy exact TXT names and values.
- Verify handles both HTTP `200` and `422` response bodies.
- The UI refetches or replaces domain data after verification.
- Existing domain add/list/detail functionality remains compatible.
- Existing singular ownership responses remain usable as a fallback.
- No Vercel API token or direct Vercel request exists in frontend code.
- Ownership values are rendered only in authenticated settings UI.
- English and Arabic layouts are supported.

## AI agent implementation brief

The following prompt can be supplied with this file to a coding agent working in the Next.js repository:

```text
Implement custom-domain ownership verification in the authenticated Next.js user dashboard using this guide as the API contract.

First inspect the existing domain settings page, API client, authentication, query/cache library, component system, translations, and tests. Reuse those patterns. Do not introduce a second HTTP or auth layer.

Add typed support for ownershipVerification, render the panel only when required is true, support apex and www records, treat sequential records as ordered steps, preserve the legacy dnsInstructions.ownership.record fallback, and handle POST /api/settings/domain/verify HTTP 422 as a valid parsed verification result. Refetch or update domain list/detail state after every parsed verification result.

Keep DNS values LTR, add English and Arabic translations, preserve existing UI behavior, do not call Vercel directly, and do not expose TXT records on public pages. Add the test cases listed in this guide. Before editing, report the exact frontend files you found and your intended changes. After implementation, run the relevant lint, typecheck, and tests and report their results.
```

## Backend source references

The current backend contract is implemented in:

- `app/Http/Controllers/Api/DomainSettingsController.php`
- `app/Models/Api/ApiDomainSetting.php`
- `app/Services/Vercel/DomainProvisioningService.php`
- `app/Services/Vercel/DomainStatusSyncService.php`
- `app/Http/Controllers/Api/GeneratedApiPathsDoc.php`

Vercel error reference: <https://vercel.com/docs/errors/deployment_not_found>
