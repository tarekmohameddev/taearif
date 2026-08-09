# WhatsApp AI Bot — Frontend Integration Guide

> Companion to [`docs/whatsapp-ai-bot-api.md`](./whatsapp-ai-bot-api.md)  
> Base URL: `/api/v1/whatsapp/`  
> Auth: Laravel Sanctum Bearer token (`Authorization: Bearer {token}`)

---

## Table of Contents

1. [Response Envelope](#1-response-envelope)
2. [Bot Configuration Screen](#2-bot-configuration-screen)
   - [Loading the config form](#21-loading-the-config-form)
   - [Saving config changes](#22-saving-config-changes)
   - [Toggle on/off switch](#23-toggle-onoff-switch)
3. [Excluded Phones Manager](#3-excluded-phones-manager)
   - [List excluded numbers](#31-list-excluded-numbers)
   - [Add a number](#32-add-a-number)
   - [Remove a number](#33-remove-a-number)
4. [Agent Inbox (Bot Needs Attention)](#4-agent-inbox-bot-needs-attention)
   - [Fetching escalated conversations](#41-fetching-escalated-conversations)
   - [Resuming the bot](#42-resuming-the-bot)
5. [Shadow Mode Inbox](#5-shadow-mode-inbox)
6. [Sandbox Simulator](#6-sandbox-simulator)
7. [Error Handling Patterns](#7-error-handling-patterns)
8. [Field Reference Quick-Card](#8-field-reference-quick-card)

---

## 1. Response Envelope

Understanding the response structure prevents the most common integration bugs.

### Success responses (`$this->ok()`)

All config-type endpoints that return a model row use a **double-wrapped** envelope:

```json
{
  "status": true,
  "data": {
    "data": { ...model fields... }
  }
}
```

Access fields at `response.data.data.*`.

### Success responses (flat list)

Endpoints that return a plain array (e.g. excluded-phones list):

```json
{
  "status": true,
  "data": [ ...items... ]
}
```

Access items at `response.data.*`.

### Error responses

```json
{
  "status": "error",
  "code": "SOME_ERROR_CODE",
  "message": "Human readable message."
}
```

Check `response.status === "error"` or HTTP status ≥ 400.

### Validation errors (Laravel 422)

```json
{
  "message": "The phone field is required.",
  "errors": {
    "phone": ["The phone field is required."]
  }
}
```

Read field errors from `response.errors.*`.

---

## 2. Bot Configuration Screen

The configuration screen lets the tenant control all AI bot settings for a specific WhatsApp number (`numberId` = `wa_numbers.id`).

### 2.1 Loading the config form

```http
GET /api/v1/whatsapp/ai/config/{numberId}
```

**JavaScript example**

```js
async function loadBotConfig(numberId) {
  const res = await api.get(`/api/v1/whatsapp/ai/config/${numberId}`);
  // config is at res.data.data (double-wrapped)
  return res.data.data;
}
```

**Fields to render**

| UI Control | Field | Type | Notes |
|---|---|---|---|
| On/Off master toggle | `enabled` | boolean | Use `PATCH .../toggle` to flip without a full save |
| Mode selector | `autonomy_level` | `off\|shadow\|autonomous` | `off` = bot disabled |
| Persona name | `assistant_name` | string | Display name shown to customers |
| Goal | `goal` | `salesman\|support\|booking` | |
| Tone | `tone` | string | Free-text: `friendly`, `formal`, etc. |
| Language | `language` | string | `ar`, `en`, etc. |
| Disclose as AI | `disclose_as_assistant` | boolean | Adds AI disclosure on first message |
| Reply length | `reply_length_target` | integer | Characters; slider 50–2000 |
| Monthly token budget | `monthly_token_budget` | integer | `0` = unlimited |
| Max tokens per turn | `max_tokens_per_turn` | integer | 200–4000 |
| Custom instructions | `custom_instructions` | textarea | Appended to every system prompt |
| Agent reply pause | `agent_reply_pause` | `off\|24h\|48h\|indefinite` | How long to pause bot after human replies |
| Business hours | `business_hours` | object | Day-keyed schedule |
| Timezone | `timezone` | string | `Asia/Riyadh`, `UTC`, etc. |

> **Config not created yet?** The API returns HTTP 404 with code `WA_AI_CONFIG_NOT_FOUND`. Show an "Enable AI Bot" prompt and create the config via `PUT`.

### 2.2 Saving config changes

```http
PUT /api/v1/whatsapp/ai/config/{numberId}
Content-Type: application/json
```

Send only changed fields — the endpoint is a partial update (PATCH semantics via PUT).

**Example — set agent-reply pause to indefinite**

```js
await api.put(`/api/v1/whatsapp/ai/config/${numberId}`, {
  agent_reply_pause: 'indefinite',
});
// returns { status: true, data: { data: { ...updatedConfig } } }
```

**`agent_reply_pause` values explained**

| Value | Behavior |
|---|---|
| `off` | Bot is never paused when a human replies |
| `24h` | Bot pauses for 24 hours after a human reply |
| `48h` | Bot pauses for 48 hours after a human reply (default) |
| `indefinite` | Bot pauses until an agent explicitly calls `POST conversations/{id}/bot/resume` |

### 2.3 Toggle on/off switch

Use the dedicated toggle endpoint for a single-field on/off flip:

```http
PATCH /api/v1/whatsapp/ai/config/{numberId}/toggle
```

```js
async function toggleBot(numberId) {
  const res = await api.patch(`/api/v1/whatsapp/ai/config/${numberId}/toggle`);
  const updatedConfig = res.data.data;
  return updatedConfig.enabled; // new value
}
```

---

## 3. Excluded Phones Manager

A sub-resource of bot config. The bot will **never** respond to numbers in this list, regardless of other settings. Use it for VIP customers, internal test lines, or numbers that must always be handled manually.

> **Phone normalization:** The server strips `+`, spaces, and dashes automatically. Display numbers in their raw form to the user but submit them as-is — the server normalizes before saving. Always show stored numbers in `966XXXXXXXXXX` format (digits only).

### 3.1 List excluded numbers

```http
GET /api/v1/whatsapp/ai/config/{numberId}/excluded-phones
```

```js
async function listExcludedPhones(numberId) {
  const res = await api.get(`/api/v1/whatsapp/ai/config/${numberId}/excluded-phones`);
  // returns flat array: res.data = [ { id, phone, created_at }, ... ]
  return res.data;
}
```

**Response shape** (`res.data`)
```json
[
  { "id": 1, "phone": "966501234567", "created_at": "2026-08-08T20:00:00+03:00" },
  { "id": 2, "phone": "966509876543", "created_at": "2026-08-08T21:00:00+03:00" }
]
```

Render as a simple list with a delete button per row.

### 3.2 Add a number

```http
POST /api/v1/whatsapp/ai/config/{numberId}/excluded-phones
Content-Type: application/json
{ "phone": "+966 50 1234567" }
```

```js
async function addExcludedPhone(numberId, rawPhone) {
  try {
    const res = await api.post(
      `/api/v1/whatsapp/ai/config/${numberId}/excluded-phones`,
      { phone: rawPhone }
    );
    // res.data = { status: 'ok', data: { id, user_id, wa_number_id, phone, ... } }
    return res.data.data; // newly created record
  } catch (err) {
    if (err.response?.data?.code === 'PHONE_ALREADY_EXCLUDED') {
      // Show: "This number is already excluded"
    }
    if (err.response?.data?.code === 'INVALID_PHONE') {
      // Show: "Please enter a valid phone number"
    }
    throw err;
  }
}
```

**Success response (HTTP 201)**
```json
{
  "status": "ok",
  "data": {
    "id": 3,
    "user_id": 12,
    "wa_number_id": 45,
    "phone": "966501234567",
    "created_at": "2026-08-08T22:00:00+03:00",
    "updated_at": "2026-08-08T22:00:00+03:00"
  }
}
```

> Note: this endpoint uses `"status": "ok"` (string), not `"status": true` (boolean). Check `res.status === 201` for success rather than `res.data.status`.

### 3.3 Remove a number

```http
DELETE /api/v1/whatsapp/ai/config/{numberId}/excluded-phones/{phoneId}
```

On success the server returns HTTP **204 No Content** (no body).

```js
async function removeExcludedPhone(numberId, phoneId) {
  await api.delete(
    `/api/v1/whatsapp/ai/config/${numberId}/excluded-phones/${phoneId}`
  );
  // 204 = deleted. Remove from local list.
}
```

**UI pattern for the exclusion list**

```
┌─ Excluded Phone Numbers ──────────────────────────────────────────────────┐
│  Numbers in this list will never receive AI bot responses.                │
│                                                                            │
│  [  +966 50 123 4567  ] [ Add ]                                           │
│                                                                            │
│  966501234567   Added Aug 8, 2026   [ Remove ]                            │
│  966509876543   Added Aug 8, 2026   [ Remove ]                            │
└───────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Agent Inbox (Bot Needs Attention)

When the bot escalates a conversation (handoff), it sets `needs_attention = true` on the conversation. Agents see these in a dedicated inbox.

### 4.1 Fetching escalated conversations

```http
GET /api/v1/whatsapp/conversations?needs_attention=1
```

Optional query params: `wa_number_id`, `per_page`, `page`.

```js
async function fetchAttentionInbox(waNumberId) {
  const res = await api.get('/api/v1/whatsapp/conversations', {
    params: { needs_attention: 1, wa_number_id: waNumberId, per_page: 50 }
  });
  // res.data.data.data = array of conversations
  // res.data.data.pagination = { current_page, per_page, total, last_page }
  return res.data.data;
}
```

Each conversation row includes:

```json
{
  "id": 88,
  "needs_attention": true,
  "handoff_reason": "customer_requested_human",
  "bot_paused_until": "2026-08-08T12:00:00+00:00"
}
```

**Displaying `handoff_reason` to agents**

| Reason | Human-readable label (suggested) |
|---|---|
| `customer_requested_human` | Customer requested a human |
| `model_needs_human` | Bot couldn't answer — needs human |
| `citation_violation` | Bot cited unverified numbers |
| `compliance` | Compliance issue detected |
| `media_message` | Media message received |
| `agent_takeover` | Agent already replied (bot paused) |

### 4.2 Resuming the bot

After the agent has handled the conversation and wants the bot to take over again:

```http
POST /api/v1/whatsapp/conversations/{id}/bot/resume
```

```js
async function resumeBot(conversationStateId) {
  try {
    const res = await api.post(
      `/api/v1/whatsapp/conversations/${conversationStateId}/bot/resume`
    );
    // { status: true, data: { bot_paused_until: null, handoff_reason: null, needs_attention: false } }
    return res.data.data;
  } catch (err) {
    if (err.response?.data?.code === 'BOT_PAUSE_NOT_RESUMABLE') {
      // Pause was caused by compliance/opt-out — cannot resume manually
      // Show: err.response.data.message
    }
    throw err;
  }
}
```

> **When is manual resume needed?**  
> Only when `agent_reply_pause = 'indefinite'` in the number config. For `24h`/`48h`, the bot resumes automatically — but agents can still trigger early resume with this endpoint.

---

## 5. Shadow Mode Inbox

When `autonomy_level = 'shadow'`, the bot generates drafts but does not send them. Agents review and approve/edit/discard each draft.

### Fetching pending drafts

```http
GET /api/v1/whatsapp/ai/bot/shadow-drafts
```

Returns a Laravel paginator with `data` array. Each draft has:

| Field | Description |
|---|---|
| `id` | Draft ID — use in the `/act` call |
| `conversation_id` | Link to the conversation |
| `draft_reply` | The text the bot would have sent |
| `confidence` | 0–100 confidence score |
| `status` | `pending` \| `approved` \| `edited` \| `discarded` |

### Acting on a draft

```http
POST /api/v1/whatsapp/ai/bot/shadow-drafts/{id}/act
Content-Type: application/json
```

| Action | Body | When |
|---|---|---|
| Approve as-is | `{ "action": "approved" }` | Draft is good; agent sends it |
| Edit and send | `{ "action": "edited", "agent_reply": "..." }` | Agent modified before sending |
| Discard | `{ "action": "discarded" }` | Draft is wrong; agent writes manually |

```js
async function approveDraft(draftId) {
  await api.post(`/api/v1/whatsapp/ai/bot/shadow-drafts/${draftId}/act`, {
    action: 'approved',
  });
}

async function editAndSendDraft(draftId, agentReply) {
  await api.post(`/api/v1/whatsapp/ai/bot/shadow-drafts/${draftId}/act`, {
    action: 'edited',
    agent_reply: agentReply,
  });
}
```

---

## 6. Sandbox Simulator

Use the simulator to test bot behaviour without sending real WhatsApp messages.

### Running a turn

```http
POST /api/v1/whatsapp/ai/bot/simulate
Content-Type: application/json
```

```js
async function simulateTurn(waNumberId, message, customerPhone = '+966500000001') {
  const res = await api.post('/api/v1/whatsapp/ai/bot/simulate', {
    wa_number_id: waNumberId,
    message,
    customer_phone: customerPhone,
  });
  return res.data; // { reply, outcome, reason, needs_human, ... }
}
```

**Key fields in the response**

| Field | Type | Description |
|---|---|---|
| `reply` | string\|null | The bot's reply text |
| `outcome` | string | `delivered` \| `shadow` \| `handoff` \| `skipped` \| `failed` |
| `reason` | string\|null | Why the bot skipped/failed/handed off |
| `needs_human` | boolean | Escalated to human? |
| `facts` | object | Extracted facts about the customer |
| `bot_paused_until` | string\|null | ISO 8601 if bot was paused this turn |

**Testing excluded numbers in the simulator**

If you simulate a turn with a `customer_phone` that is in the number's exclusion list:

```json
{
  "reply": null,
  "outcome": "skipped",
  "reason": "excluded_number",
  "needs_human": false
}
```

### Resetting the sandbox

Call reset between test scenarios:

```js
async function resetSimulator(waNumberId, customerPhone = '+966500000001') {
  const res = await api.post('/api/v1/whatsapp/ai/bot/simulate/reset', {
    wa_number_id: waNumberId,
    customer_phone: customerPhone,
  });
  return res.data.cleared; // true if a conversation existed
}
```

---

## 7. Error Handling Patterns

### Centralised error handler

```js
function handleApiError(err) {
  const data = err.response?.data;
  const httpStatus = err.response?.status;

  if (!data) {
    return { type: 'network', message: 'Network error. Please retry.' };
  }

  // Laravel validation errors
  if (httpStatus === 422 && data.errors) {
    const fieldErrors = Object.entries(data.errors)
      .map(([field, msgs]) => `${field}: ${msgs.join(', ')}`)
      .join('\n');
    return { type: 'validation', message: fieldErrors, errors: data.errors };
  }

  // Structured API errors
  if (data.status === 'error' || data.code) {
    return { type: data.code ?? 'api_error', message: data.message };
  }

  return { type: 'unknown', message: 'An unexpected error occurred.' };
}
```

### Common error codes

| Code | When it appears | Suggested UI message |
|---|---|---|
| `WA_NUMBER_NOT_FOUND` | Wrong `numberId` | "WhatsApp number not found." |
| `WA_AI_CONFIG_NOT_FOUND` | Config not created yet | Show "Set up AI Bot" CTA |
| `WA_CONVERSATION_NOT_FOUND` | Wrong conversation ID | "Conversation not found." |
| `PHONE_ALREADY_EXCLUDED` | Duplicate in exclusion list | "This number is already excluded." |
| `INVALID_PHONE` | Phone has no digits | "Please enter a valid phone number." |
| `BOT_PAUSE_NOT_RESUMABLE` | Non-agent-takeover pause | Show `err.response.data.message` directly |

---

## 8. Field Reference Quick-Card

### `agent_reply_pause` values

| Value | Stored | Effect |
|---|---|---|
| `off` | `"off"` | Bot never pauses after human replies |
| `24h` | `"24h"` | Bot pauses 24 h after human reply |
| `48h` | `"48h"` | Bot pauses 48 h after human reply (default) |
| `indefinite` | `"indefinite"` | Bot pauses until `POST conversations/{id}/bot/resume` |

### `autonomy_level` values

| Value | Effect |
|---|---|
| `off` | Bot fully disabled |
| `shadow` | Bot generates drafts for agent approval; nothing sent automatically |
| `autonomous` | Bot sends replies directly |

### `needs_attention` on a conversation

A conversation has `needs_attention = true` when:
- `bot_paused_until` is in the future, **AND**
- `handoff_reason` is **not** `agent_takeover`

Agents should act on these. When `handoff_reason = agent_takeover`, the bot paused itself because a human already replied — no action required unless the tenant uses `indefinite` pause.

### Excluded phones — phone format

The API stores and returns phone numbers as **digits only** (E.164 without `+`):

- Input: `+966 50 123-4567` → stored as `966501234567`
- Input: `00966501234567` → stored as `00966501234567` (leading zeros preserved)

Display the stored `phone` value with a `+` prefix for readability (`+${phone}`), but always submit the raw user input to the API and let the server normalize.
