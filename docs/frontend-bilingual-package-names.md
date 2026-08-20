# Bilingual package names — SPA handoff

Catalogs now return **both** Arabic and English. The SPA at `FRONTEND_URL` must pick the label. Do **not** rely on `Accept-Language`.

Credits still honour `?locale=` on the collapsed `name` / `description` only. For Arabic UI, prefer the raw `_ar` fields.

## Client rule (use everywhere)

```js
const label = locale === 'ar' ? (name_ar || name) : (name_en || name);
```

Same idea for titles:

```js
const title = locale === 'ar' ? (title_ar || title) : (title_en || title);
```

`_ar` / `_en` may be `null` on older rows — always fall back to `name` / `title`.

## Highest priority

WhatsApp modal **شراء رصيد واتساب** currently shows English because it reads `package.name`. After this API it must use `name_ar` when the UI locale is Arabic.

Same rule for subscription plan cards and WhatsApp / employee addon plan pickers.

## Endpoints

All paths are under the `/api` prefix. Changes are **additive** — existing `name` / `title` keep their exact meaning.

### 1. `GET /api/v1/credits/packages` (public)

`name` / `description` still locale-collapsed via `?locale=` (default `en`).

New raw columns (always present, not collapsed):

| Field | Meaning |
|---|---|
| `name_ar` | Arabic name (may be `null`) |
| `name_en` | English `name` column |
| `description_ar` | Arabic description (may be `null`) |
| `description_en` | English `description` column |

For Arabic UI: use `name_ar` / `description_ar`, **not** `name` (default locale is `en`).

```json
{
  "name": "Starter Pack",
  "name_en": "Starter Pack",
  "name_ar": "باقة البداية",
  "description": "…",
  "description_en": "…",
  "description_ar": "…"
}
```

### 2. `GET /api/v1/credits/transactions` (auth)

Nested `package.name` is unchanged (English).

New: `package.name_ar`, `package.name_en`.

```json
{
  "package": {
    "id": 1,
    "name": "Starter Pack",
    "name_en": "Starter Pack",
    "name_ar": "باقة البداية",
    "credits": 100
  }
}
```

### 3. `GET /api/settings/payment` (auth)

`name` is still the Arabic title.

| Field | Meaning |
|---|---|
| `name` | Arabic title (unchanged) |
| `name_ar` | Same as `name` / `title` |
| `name_en` | English title (may be `null`) |
| `billing` | Hardcoded Arabic (`شهريًا` / `سنويًا` / `تجريبي`) |
| `billing_key` | `monthly` \| `yearly` \| `trial` \| `null` |
| `cta` | Hardcoded Arabic (`الخطة الحالية` / `الترقية`) |
| `cta_key` | `current` \| `upgrade` |

Use `billing_key` / `cta_key` if you want to translate billing/cta in the SPA later.

```json
{
  "id": 1,
  "name": "الباقة الشهرية",
  "name_ar": "الباقة الشهرية",
  "name_en": "Monthly Package",
  "billing": "شهريًا",
  "billing_key": "monthly",
  "cta": "الترقية",
  "cta_key": "upgrade"
}
```

### 4. Membership package on user profile (auth)

`GET /api/user` and alias `GET /api/user/getUserInfo`.

`membership.package.title` is unchanged (Arabic).

New: `title_ar` (same as `title`), `title_en` (may be `null`).

```json
{
  "membership": {
    "package": {
      "title": "الباقة الشهرية",
      "title_ar": "الباقة الشهرية",
      "title_en": "Monthly Package"
    }
  }
}
```

### 5. `GET /api/whatsapp/addons/plans` (auth + active membership)

Not under `/api/v1`. Verified in `routes/api.php`.

`name` is unchanged (English stored label).

New: `name_ar` (`null` until admin fills it), `name_en` (= `name`).

```json
{
  "id": 1,
  "name": "WhatsApp Extra Number",
  "name_ar": null,
  "name_en": "WhatsApp Extra Number"
}
```

### 6. Employee addon plans (auth + active membership)

Same shape as WhatsApp.

| Method | Path | Where |
|---|---|---|
| `GET` | `/api/employee/addons/plans` | `data.plans[]` |
| `GET` | `/api/employee/addons` | nested `plan` on each addon |

```json
{
  "id": 1,
  "name": "Extra Employee Seat",
  "name_ar": null,
  "name_en": "Extra Employee Seat"
}
```

## Compatibility

No breaking change. Old fields keep their exact meaning. The SPA can ship independently.

Until the SPA switches, Arabic UI keeps showing English on credits / addons (and on any addon plan whose `name_ar` is still `null`).

## Out of scope

`features` / `new_features` are not bilingual yet. Leave them as-is.
