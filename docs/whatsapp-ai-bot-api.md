# WhatsApp AI Bot — API Reference

> Base URL: `/api/v1/whatsapp/`  
> Authentication: Laravel Sanctum Bearer token (`Authorization: Bearer {token}`)  
> All responses: `Content-Type: application/json`  
> All timestamps: ISO 8601, UTC

---

## Table of Contents

1. [Bot Configuration](#1-bot-configuration)
   - [GET ai/config/{numberId}](#11-get-aiconfignumberid)
   - [PUT ai/config/{numberId}](#12-put-aiconfignumberid)
   - [PATCH ai/config/{numberId}/toggle](#13-patch-aiconfignumberidtoggle)
   - [GET ai/stats](#14-get-aistats)
2. [Quality Dashboard](#2-quality-dashboard)
   - [GET ai/bot/dashboard](#21-get-aibotdashboard)
3. [Shadow Mode Inbox](#3-shadow-mode-inbox)
   - [GET ai/bot/shadow-drafts](#31-get-aibotshadow-drafts)
   - [POST ai/bot/shadow-drafts/{id}/act](#32-post-aibotshadow-draftsidact)
   - [GET conversations?needs_attention=1](#33-get-conversationsneeds_attention1)
   - [POST conversations/{id}/bot/resume](#34-post-conversationsidbotresume)
   - [Excluded Phones sub-resource](#35-excluded-phones-bot-number-exclusion)
4. [Unanswered Questions](#4-unanswered-questions)
   - [POST ai/bot/unanswered/{id}/mark-faq](#41-post-aibotunansweredidmark-faq)
5. [Sandbox Simulator](#5-sandbox-simulator)
   - [POST ai/bot/simulate](#51-post-aibotsimulate)
   - [GET ai/bot/simulate/conversation](#52-get-aibotsimulateconversation)
   - [POST ai/bot/simulate/reset](#53-post-aibotsimulatereset)
6. [Knowledge Base](#6-knowledge-base)
   - [GET ai/knowledge](#61-get-aiknowledge)
   - [POST ai/knowledge](#62-post-aiknowledge)
   - [GET ai/knowledge/{id}](#63-get-aiknowledgeid)
   - [PATCH ai/knowledge/{id}](#64-patch-aiknowledgeid)
   - [DELETE ai/knowledge/{id}](#65-delete-aiknowledgeid)
7. [FAQ Candidates](#7-faq-candidates)
   - [GET ai/faq-candidates](#71-get-aifaq-candidates)
   - [PATCH ai/faq-candidates/{id}](#72-patch-aifaq-candidatesid)
8. [Property External Links](#8-property-external-links)
   - [GET properties/{propertyId}/external-links](#81-get-propertiespropertyidexternal-links)
   - [POST properties/{propertyId}/external-links](#82-post-propertiespropertyidexternal-links)
   - [PATCH properties/{propertyId}/external-links/{linkId}](#83-patch-propertiespropertyidexternal-linkslinkid)
   - [DELETE properties/{propertyId}/external-links/{linkId}](#84-delete-propertiespropertyidexternal-linkslinkid)
9. [Flow Diagrams](#9-flow-diagrams)
   - [Complete Bot Turn](#91-complete-bot-turn)
   - [Shadow Mode Lifecycle](#92-shadow-mode-lifecycle)
   - [Simulator Flow](#93-simulator-flow)
10. [Error Reference](#10-error-reference)

### Endpoint index

| Method | Path | Purpose |
|---|---|---|
| `GET` | `ai/config/{numberId}` | Fetch bot config for a number |
| `PUT` | `ai/config/{numberId}` | Create / update bot config |
| `PATCH` | `ai/config/{numberId}/toggle` | Toggle `enabled` |
| `GET` | `ai/stats` | Tenant AI usage summary |
| `GET` | `ai/bot/dashboard` | Quality-loop metrics |
| `GET` | `ai/bot/shadow-drafts` | Pending shadow drafts |
| `POST` | `ai/bot/shadow-drafts/{id}/act` | Approve / edit / discard draft |
| `GET` | `conversations?needs_attention=1` | Agent inbox: bot-escalated chats |
| `POST` | `conversations/{id}/bot/resume` | Resume bot after agent-takeover pause |
| `GET` | `ai/config/{numberId}/excluded-phones` | List numbers excluded from AI bot |
| `POST` | `ai/config/{numberId}/excluded-phones` | Add a number to the exclusion list |
| `DELETE` | `ai/config/{numberId}/excluded-phones/{phoneId}` | Remove a number from the exclusion list |
| `POST` | `ai/bot/unanswered/{id}/mark-faq` | Mark gap question as FAQ-resolved |
| `POST` | `ai/bot/simulate` | Run one sandbox bot turn |
| `GET` | `ai/bot/simulate/conversation` | Sandbox transcript |
| `POST` | `ai/bot/simulate/reset` | Clear sandbox conversation |
| `GET` | `ai/knowledge` | List knowledge sources |
| `POST` | `ai/knowledge` | Create + index a knowledge source |
| `GET` | `ai/knowledge/{id}` | Show one knowledge source |
| `PATCH` | `ai/knowledge/{id}` | Update / re-index a source |
| `DELETE` | `ai/knowledge/{id}` | Delete source + chunks |
| `GET` | `ai/faq-candidates` | List mined FAQ candidates |
| `PATCH` | `ai/faq-candidates/{id}` | Correct / reject a candidate |
| `GET` | `properties/{propertyId}/external-links` | List listing URLs for a property |
| `POST` | `properties/{propertyId}/external-links` | Attach a portal listing URL |
| `PATCH` | `properties/{propertyId}/external-links/{linkId}` | Update a listing URL |
| `DELETE` | `properties/{propertyId}/external-links/{linkId}` | Remove a listing URL |

---

## 1. Bot Configuration

### 1.1 GET ai/config/{numberId}

Fetch the AI bot configuration for a specific WhatsApp number.

**Path params**

| Param | Type | Description |
|---|---|---|
| `numberId` | integer | ID from `wa_numbers` table |

**Request**
```http
GET /api/v1/whatsapp/ai/config/45
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "status": true,
  "data": {
    "data": {
      "id": 12,
      "user_id": 123,
      "wa_number_id": 45,
      "enabled": true,
      "autonomy_level": "shadow",
      "goal": "salesman",
      "tone": "friendly",
      "language": "ar",
      "assistant_name": "نورة",
      "disclose_as_assistant": true,
      "reply_length_target": 200,
      "confidence_threshold": 70,
      "groundedness_threshold": 80,
      "fallback_to_human": true,
      "monthly_token_budget": 500000,
      "max_tokens_per_turn": 800,
      "agent_reply_pause": "48h",
      "custom_instructions": "لا تقبل عمولة أقل من 2.5%",
      "playbook": {
        "few_shot_examples": [
          {
            "customer": "كم سعر الشقة؟",
            "bot": "سعر الشقة {{p:1301|price}} ريال. هل تودّ معرفة المزيد؟"
          }
        ]
      },
      "business_hours": {
        "sunday":    { "open": true,  "from": "09:00", "to": "21:00" },
        "monday":    { "open": true,  "from": "09:00", "to": "21:00" },
        "tuesday":   { "open": true,  "from": "09:00", "to": "21:00" },
        "wednesday": { "open": true,  "from": "09:00", "to": "21:00" },
        "thursday":  { "open": true,  "from": "09:00", "to": "21:00" },
        "friday":    { "open": false },
        "saturday":  { "open": true,  "from": "10:00", "to": "18:00" }
      },
      "timezone": "Asia/Riyadh",
      "scenarios": null,
      "escalation_rules": null,
      "excluded_phones": [
        { "id": 1, "phone": "966501234567", "created_at": "2026-08-08T20:00:00+03:00" },
        { "id": 2, "phone": "966509876543", "created_at": "2026-08-08T21:00:00+03:00" }
      ],
      "created_at": "2026-08-02T09:00:00+03:00",
      "updated_at": "2026-08-02T10:00:00+03:00"
    }
  }
}
```

> **Response envelope:** All config endpoints use a nested `data.data` wrapper — access config fields at `response.data.data.*`.

**Response 404**
```json
{
  "status": "error",
  "code": "WA_AI_CONFIG_NOT_FOUND",
  "message": "AI config for this number not found."
}
```

---

### 1.2 PUT ai/config/{numberId}

Create or update the bot configuration for a number. Partial updates are supported — only sent fields are changed.

**Path params**

| Param | Type | Description |
|---|---|---|
| `numberId` | integer | ID from `wa_numbers` table |

**Request body** (all fields optional)

| Field | Type | Values | Description |
|---|---|---|---|
| `enabled` | boolean | `true\|false` | Master on/off switch |
| `autonomy_level` | string | `off\|shadow\|autonomous` | Bot behavior mode (`off` = disabled) |
| `goal` | string | `salesman\|support\|booking` | Bot role / persona |
| `tone` | string | — | Conversational tone (e.g. `friendly`, `formal`, `enthusiastic`) |
| `language` | string | — | Primary reply language (e.g. `ar`, `en`) |
| `assistant_name` | string | — | Display name shown in the system prompt (e.g. `نورة`) |
| `disclose_as_assistant` | boolean | — | Honest AI disclosure on first contact |
| `reply_length_target` | integer | 50–2000 | Target chars per reply |
| `confidence_threshold` | integer | 0–100 | Below this → handoff (legacy; agent loop uses its own escalation tool) |
| `groundedness_threshold` | integer | 0–100 | Reserved for future use |
| `fallback_to_human` | boolean | — | Enable human escalation |
| `fallback_delay` | integer | ≥0 | Seconds to wait before escalating when fallback is enabled |
| `monthly_token_budget` | integer | ≥0 | Maximum tokens allowed per month for this number. `0` = unlimited |
| `max_tokens_per_turn` | integer | 200–4000 | Per-turn LLM completion-token ceiling. Defaults to 800 |
| `custom_instructions` | string | — | Free-text instructions appended to every system prompt |
| `playbook` | object | — | Advanced persona overrides (see below) |
| `business_hours` | object | — | Day-keyed schedule (see shape below) |
| `business_hours_only` | boolean | — | Legacy flag: restrict replies to business hours |
| `business_hours_start` | string | `H:i` | Legacy single start time (prefer `business_hours`) |
| `business_hours_end` | string | `H:i` | Legacy single end time (prefer `business_hours`) |
| `timezone` | string | — | Timezone for business hours (e.g. `Asia/Riyadh`) |
| `scenarios` | array | — | Optional scenario overrides |
| `escalation_rules` | array | — | Custom escalation conditions |
| `agent_reply_pause` | string | `off\|24h\|48h\|indefinite` | How long to pause the AI bot after a human agent replies (mobile app or CRM). `off` = never pause. `indefinite` = pause until manually resumed via `POST conversations/{id}/bot/resume`. Default: `48h` |

> **Note:** The `excluded_phones` list is managed via the dedicated sub-resource endpoints below, not via this PUT body.

**`playbook` shape**

The `playbook` object can override or extend any of the top-level persona fields and add few-shot examples:

```json
{
  "few_shot_examples": [
    {
      "customer": "كم سعر الشقة؟",
      "bot": "سعر الشقة {{p:1301|price}} ريال وتقع في {{p:1301|address}}."
    }
  ]
}
```

**`business_hours` shape**
```json
{
  "sunday":  { "open": true,  "from": "09:00", "to": "21:00" },
  "friday":  { "open": false }
}
```

**Request example — enable bot in shadow mode**
```http
PUT /api/v1/whatsapp/ai/config/45
Authorization: Bearer {token}
Content-Type: application/json

{
  "enabled": true,
  "autonomy_level": "shadow",
  "goal": "salesman",
  "assistant_name": "نورة",
  "tone": "friendly",
  "disclose_as_assistant": true,
  "monthly_token_budget": 500000,
  "max_tokens_per_turn": 800,
  "custom_instructions": "نركز على مشاريع الرياض فقط. لا تقبل عمولة أقل من 2.5%.",
  "timezone": "Asia/Riyadh",
  "business_hours": {
    "sunday":    { "open": true, "from": "09:00", "to": "21:00" },
    "monday":    { "open": true, "from": "09:00", "to": "21:00" },
    "tuesday":   { "open": true, "from": "09:00", "to": "21:00" },
    "wednesday": { "open": true, "from": "09:00", "to": "21:00" },
    "thursday":  { "open": true, "from": "09:00", "to": "21:00" },
    "friday":    { "open": false },
    "saturday":  { "open": true, "from": "10:00", "to": "18:00" }
  }
}
```

**Request example — graduate to autonomous**
```http
PUT /api/v1/whatsapp/ai/config/45
Authorization: Bearer {token}
Content-Type: application/json

{
  "autonomy_level": "autonomous"
}
```

**Response 200** — same envelope as GET (`data.data` nesting)
```json
{
  "status": true,
  "data": {
    "data": {
      "id": 12,
      "wa_number_id": 45,
      "enabled": true,
      "autonomy_level": "shadow",
      "goal": "salesman",
      "assistant_name": "نورة",
      "max_tokens_per_turn": 800,
      "updated_at": "2026-08-02T10:15:00+03:00"
    }
  }
}
```

**Response 404**
```json
{
  "status": "error",
  "code": "WA_NUMBER_NOT_FOUND",
  "message": "WhatsApp number not found."
}
```

---

### 1.3 PATCH ai/config/{numberId}/toggle

Toggle `enabled` between `true` and `false` without sending a full body.

**Request**
```http
PATCH /api/v1/whatsapp/ai/config/45/toggle
Authorization: Bearer {token}
```

**Response 200** — same `data.data` envelope
```json
{
  "status": true,
  "data": {
    "data": {
      "id": 12,
      "wa_number_id": 45,
      "enabled": false,
      "updated_at": "2026-08-02T10:20:00+03:00"
    }
  }
}
```

---

### 1.4 GET ai/stats

Overall AI usage statistics for the authenticated tenant.

**Request**
```http
GET /api/v1/whatsapp/ai/stats
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "status": true,
  "data": {
    "data": {
      "total_suggestions": 1240,
      "suggestions_today": 38,
      "conversations_with_ai": 312,
      "avg_confidence": 82.4
    }
  }
}
```

---

## 2. Quality Dashboard

### 2.1 GET ai/bot/dashboard

Returns the quality loop metrics for the bot: token usage, shadow draft edit-rate, handoff reasons, unanswered question gaps, and last evaluation run scores.

**Query params**

| Param | Type | Default | Description |
|---|---|---|---|
| `period` | string | `30d` | `7d`, `30d`, or `90d` |

**Request**
```http
GET /api/v1/whatsapp/ai/bot/dashboard?period=30d
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "period": "30d",
  "since": "2026-07-03T07:48:00+00:00",
  "usage": {
    "total_tokens": 2847300,
    "total_calls": 4120,
    "failed_calls": 3,
    "avg_latency_ms": 1840,
    "cost_usd": 0.7118
  },
  "mining_spend": {
    "calls": 12,
    "tokens": 48000,
    "cost_usd": 0.012
  },
  "shadow": {
    "total": 210,
    "approved": 168,
    "edited": 28,
    "discarded": 14,
    "avg_confidence": 81,
    "edit_rate_pct": 20.0
  },
  "quality_rates": {
    "grounding_failure_rate_pct": 1.2,
    "handoff_rate_pct": 8.5
  },
  "handoff_reasons": [
    { "handoff_reason": "customer_requested_human",  "count": 22 },
    { "handoff_reason": "model_needs_human",          "count": 18 },
    { "handoff_reason": "citation_violation",         "count": 9  },
    { "handoff_reason": "budget_exhausted",           "count": 4  }
  ],
  "top_unanswered": [
    { "id": 14, "question": "كم رسوم التسجيل في الصكوك؟", "occurrence_count": 7,  "cluster_key": "a3f9..." },
    { "id": 22, "question": "هل يقبلون دفع مقدم 10%؟",    "occurrence_count": 5,  "cluster_key": "b8c1..." },
    { "id": 31, "question": "ما هي شروط التمويل؟",          "occurrence_count": 3,  "cluster_key": "d2e4..." }
  ],
  "last_eval": {
    "run_id": "2026-08-01-a3f9b2",
    "passed": true,
    "scores": {
      "groundedness": 88.5,
      "dialect":      91.0,
      "task_success": 79.5,
      "handoff":      95.0,
      "length":       87.0
    },
    "passed_turns": 142,
    "total_turns":  156,
    "created_at": "2026-08-01T03:00:00+00:00"
  }
}
```

**Field glossary**

| Field | Description |
|---|---|
| `usage.cost_usd` | Estimated production LLM cost in USD (`cost_micros / 1,000,000`). Excludes `simulate` and `mine` pass types |
| `mining_spend` | Separate spend bucket for FAQ/knowledge mine jobs (`pass_type = mine`) |
| `shadow.edit_rate_pct` | `(edited + discarded) / total × 100`. Graduate to autonomous when < 15%. `null` when no drafts |
| `quality_rates.grounding_failure_rate_pct` | Failed `generate` passes / total generate passes in period |
| `quality_rates.handoff_rate_pct` | Conversations with a handoff reason / conversations touched in period |
| `handoff_reasons` | Top 10 reasons bot handed off, sorted by frequency |
| `top_unanswered` | Questions bot failed to answer (not yet added to FAQ). Use to grow knowledge base |
| `last_eval.passed` | `true` when `groundedness ≥ 75`, `task_success ≥ 70`, `dialect ≥ 70` |

---

## 3. Shadow Mode Inbox

### 3.1 GET ai/bot/shadow-drafts

Returns paginated list of pending bot drafts awaiting agent decision.

**Request**
```http
GET /api/v1/whatsapp/ai/bot/shadow-drafts
Authorization: Bearer {token}
```

**Response 200** (Laravel paginator envelope)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 88,
      "conversation_id": 4201,
      "user_id": 123,
      "trigger_message_id": 9832,
      "draft_reply": "يسعدنا! لدينا شقة 3 غرف في حي النزهة. المساحة 145 م². متى يناسبك الزيارة؟",
      "used_sources": [1301, 1302],
      "confidence": 87,
      "status": "pending",
      "agent_reply": null,
      "agent_id": null,
      "acted_at": null,
      "created_at": "2026-08-02T10:30:00+03:00",
      "updated_at": "2026-08-02T10:30:00+03:00"
    },
    {
      "id": 87,
      "conversation_id": 4198,
      "trigger_message_id": 9820,
      "draft_reply": "الإيجار السنوي يبدأ من ثمانية وعشرين ألف ريال للشقق الاستوديو. هل تريد تفاصيل أكثر؟",
      "used_sources": [],
      "confidence": 74,
      "status": "pending",
      "created_at": "2026-08-02T10:25:00+03:00"
    }
  ],
  "first_page_url": "https://app.taearif.com/api/v1/whatsapp/ai/bot/shadow-drafts?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "https://app.taearif.com/api/v1/whatsapp/ai/bot/shadow-drafts?page=3",
  "next_page_url": "https://app.taearif.com/api/v1/whatsapp/ai/bot/shadow-drafts?page=2",
  "per_page": 20,
  "total": 42
}
```

> **Note:** `used_sources` is now an array of property IDs (integers), not knowledge-chunk keys.

---

### 3.2 POST ai/bot/shadow-drafts/{id}/act

Agent approves, edits, or discards a pending shadow draft.

**Path params**

| Param | Type | Description |
|---|---|---|
| `id` | integer | Shadow draft ID |

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `action` | string | yes | `approved` \| `edited` \| `discarded` |
| `agent_reply` | string | if `edited` | The text the agent actually sent (for edit-rate tracking) |

**Example — approve as-is**
```http
POST /api/v1/whatsapp/ai/bot/shadow-drafts/88/act
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "approved"
}
```

**Example — edit and record what was sent**
```http
POST /api/v1/whatsapp/ai/bot/shadow-drafts/88/act
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "edited",
  "agent_reply": "لدينا شقة 3 غرف في النزهة، المساحة 145 م². أرسل لك الصور الآن."
}
```

**Example — discard**
```http
POST /api/v1/whatsapp/ai/bot/shadow-drafts/88/act
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "discarded"
}
```

**Response 200**
```json
{
  "success": true,
  "status": "approved"
}
```

**Response 409** — draft already acted on
```json
{
  "error": "Draft is no longer pending."
}
```

**Response 422** — validation error
```json
{
  "message": "The action field is required.",
  "errors": {
    "action": ["The action field is required."]
  }
}
```

---

### 3.3 GET conversations?needs_attention=1

Agent handoff inbox. Filters the existing WhatsApp conversations list to chats where the bot has been paused and a human must reply.

This is **not** the same as `status=pending` (CRM workflow status on `wa_conversation_states`). Needs-attention comes from `wa_conversation_ai_states.bot_paused_until` / `handoff_reason`.

**Query params** (all optional; combinable with existing filters)

| Param | Type | Description |
|---|---|---|
| `needs_attention` | boolean-ish | `1` / `true` / `yes` → only escalated chats |
| `wa_number_id` | integer | Limit to one WhatsApp number |
| `status` | string | CRM status: `active` \| `pending` \| `resolved` |
| `search` | string | Match customer phone / identifier |
| `per_page` | integer | Page size (max 100) |
| `page` | integer | Page number |

**Request**
```http
GET /api/v1/whatsapp/conversations?needs_attention=1&wa_number_id=2&per_page=50&page=1
Authorization: Bearer {token}
```

**Included / excluded**

| Included | Excluded |
|---|---|
| Bot paused (`bot_paused_until` in the future) with a handoff reason such as `customer_requested_human`, `model_needs_human`, `citation_violation`, `compliance`, … | `handoff_reason = agent_takeover` (human already replied) |
| | Expired pauses (`bot_paused_until` in the past) |
| | Conversations with no AI pause |

**Response fields** (on every conversation row, including unfiltered lists)

| Field | Type | Description |
|---|---|---|
| `needs_attention` | boolean | `true` when bot is paused for a non-takeover handoff |
| `handoff_reason` | string\|null | Why the bot paused |
| `bot_paused_until` | string\|null | ISO 8601 pause expiry |

**Response 200** (excerpt)
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 88,
        "state_id": 88,
        "conversation_id": 4201,
        "user_id": 123,
        "wa_number_id": 2,
        "status": "active",
        "unread_count": 1,
        "needs_attention": true,
        "handoff_reason": "customer_requested_human",
        "bot_paused_until": "2026-08-08T12:00:00+00:00",
        "last_message_preview": "أبي أتكلم مع موظف",
        "last_message_time": "2026-08-07T20:55:00+00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 1,
      "last_page": 1
    }
  }
}
```

---

### 3.4 POST conversations/{id}/bot/resume

Resume the AI bot for a conversation that was paused because a human agent replied (`handoff_reason = agent_takeover`). This is required when `agent_reply_pause` is set to `indefinite` on the number config, but also works for `24h`/`48h` pauses.

> Safety pauses caused by compliance violations, media review, or customer opt-out cannot be cleared with this endpoint. Those return `422`.

**Path params**

| Param | Type | Description |
|---|---|---|
| `id` | integer | Conversation state ID (`wa_conversation_states.id`) or `conversation_id` |

**Request**
```http
POST /api/v1/whatsapp/conversations/88/bot/resume
Authorization: Bearer {token}
```

**Response 200** — bot resumed
```json
{
  "status": true,
  "data": {
    "bot_paused_until": null,
    "handoff_reason": null,
    "needs_attention": false
  }
}
```

**Response 404** — conversation or AI state not found
```json
{
  "status": "error",
  "code": "WA_CONVERSATION_NOT_FOUND",
  "message": "Conversation not found."
}
```

**Response 422** — pause reason is not `agent_takeover`
```json
{
  "status": "error",
  "code": "BOT_PAUSE_NOT_RESUMABLE",
  "message": "The bot cannot be manually resumed while paused for: compliance."
}
```

---

### 3.5 Excluded Phones (Bot Number Exclusion) {#35-excluded-phones-bot-number-exclusion}

These three endpoints manage the list of customer WhatsApp numbers that the AI bot will **never** engage with, regardless of any other configuration. Useful for VIP customers, internal test numbers, or any number that must always be handled by a human.

> **Phone format:** Enter digits only (E.164 without `+`), e.g. `966501234567`. The server strips `+`, spaces, and dashes automatically on save.

#### GET ai/config/{numberId}/excluded-phones

List all excluded numbers for a WhatsApp number.

```http
GET /api/v1/whatsapp/ai/config/45/excluded-phones
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "status": true,
  "data": [
    { "id": 1, "phone": "966501234567", "created_at": "2026-08-08T20:00:00+03:00" },
    { "id": 2, "phone": "966509876543", "created_at": "2026-08-08T21:00:00+03:00" }
  ]
}
```

#### POST ai/config/{numberId}/excluded-phones

Add a number to the exclusion list.

| Field | Type | Required | Notes |
|---|---|---|---|
| `phone` | string | Yes | Customer phone number. The server normalizes to digits only (strips `+`, spaces, dashes). Max 20 chars before normalization. |

```http
POST /api/v1/whatsapp/ai/config/45/excluded-phones
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone": "+966 50 1234567"
}
```

**Response 201** — number added
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

**Response 422** — duplicate
```json
{
  "status": "error",
  "code": "PHONE_ALREADY_EXCLUDED",
  "message": "This phone number is already in the exclusion list."
}
```

#### DELETE ai/config/{numberId}/excluded-phones/{phoneId}

Remove a number from the exclusion list by its row ID.

```http
DELETE /api/v1/whatsapp/ai/config/45/excluded-phones/3
Authorization: Bearer {token}
```

**Response 204** — no content (deleted successfully)

**Response 404** — record not found
```json
{
  "status": "error",
  "code": "NOT_FOUND",
  "message": "Excluded phone record not found."
}
```

---

## 4. Unanswered Questions

### 4.1 POST ai/bot/unanswered/{id}/mark-faq

Mark an unanswered question as resolved (added to FAQ / knowledge base). Removes it from the gap report.

**Path params**

| Param | Type | Description |
|---|---|---|
| `id` | integer | `bot_unanswered_questions.id` |

**Request**
```http
POST /api/v1/whatsapp/ai/bot/unanswered/14/mark-faq
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "success": true
}
```

**Response 404** — question not found for this tenant
```json
{
  "message": "No query results for model [App\\Models\\BotUnansweredQuestion] 14"
}
```

---

## 5. Sandbox Simulator

The sandbox runs the **full** `Employee` pipeline (compliance → AgentLoop with tool-calling → CitationGuard → ReplyRenderer → PolicyGate) but never sends anything to WhatsApp and does not deduct credits. Every turn is persisted in an isolated `whatsapp_sandbox` conversation so multi-turn context, AI state (facts, paused status, disclosure) and rolling summarisation work exactly as in production.

> **Note:** Only the authenticated tenant can simulate their own numbers. Platform admins can simulate any tenant by passing a different `tenant_id`.

### 5.1 POST ai/bot/simulate

Run one bot turn.

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `wa_number_id` | integer | yes | WhatsApp number ID with an existing `wa_ai_config` |
| `message` | string | yes | The customer message to simulate (max 1,000 chars) |
| `customer_phone` | string | no | Simulated customer phone. Defaults to `+966500000001`. Use a consistent value across turns to continue the same conversation. |
| `tenant_id` | integer | no | Defaults to authenticated user. Admins can override. |
| `include_transcript` | boolean | no | When `true`, response also includes the full sandbox `transcript` object (same shape as §5.2) |

**Request example — first turn**
```http
POST /api/v1/whatsapp/ai/bot/simulate
Authorization: Bearer {token}
Content-Type: application/json

{
  "wa_number_id": 45,
  "message": "أبحث عن شقة 3 غرف في حي النزهة بالرياض ميزانيتي 650 ألف",
  "customer_phone": "+966501234567"
}
```

**Request example — second turn (same `customer_phone` → same conversation)**
```http
POST /api/v1/whatsapp/ai/bot/simulate
Authorization: Bearer {token}
Content-Type: application/json

{
  "wa_number_id": 45,
  "message": "وش الأرخص عندكم؟",
  "customer_phone": "+966501234567"
}
```

**Response 200 — successful delivery**
```json
{
  "reply": "وجدت لك شقة مناسبة في حي النزهة. السعر مناسب لميزانيتك ومساحتها جيدة. هل تودّ معرفة التفاصيل أو تحديد موعد معاينة؟",
  "outcome": "delivered",
  "reason": "delivered",
  "needs_human": false,
  "handoff_reason": null,
  "conversation_id": 1001,
  "turn_index": 1,
  "bot_messages": [
    "وجدت لك شقة مناسبة في حي النزهة. السعر مناسب لميزانيتك ومساحتها جيدة. هل تودّ معرفة التفاصيل أو تحديد موعد معاينة؟"
  ],
  "bot_paused_until": null,
  "handoff_reason_state": null,
  "facts": {
    "city": "الرياض",
    "district": "النزهة",
    "bedrooms": 3,
    "budget_max": 650000,
    "intent": "search",
    "is_first_contact": false,
    "disclosed_as_assistant": true
  },
  "opt_out_status": "active"
}
```

**Response 200 — handoff triggered**
```json
{
  "reply": "عذراً على الإزعاج. سأحوّلك لأحد موظفينا للمساعدة.",
  "outcome": "handoff",
  "reason": "citation_violation",
  "needs_human": true,
  "handoff_reason": "citation_violation",
  "conversation_id": 1001,
  "turn_index": 3,
  "bot_messages": ["عذراً على الإزعاج. سأحوّلك لأحد موظفينا للمساعدة."],
  "bot_paused_until": "2026-08-06T12:00:00+03:00",
  "handoff_reason_state": "citation_violation",
  "facts": { "city": "جدة", "property_type": "عمارة" },
  "opt_out_status": "active"
}
```

**Response 200 — `autonomy_level = 'shadow'`**

Draft is generated but not delivered. `outcome = "shadow"`.

```json
{
  "reply": "أهلاً وسهلاً...",
  "outcome": "shadow",
  "reason": null,
  "needs_human": false,
  "handoff_reason": null,
  "conversation_id": 1001,
  "turn_index": 1,
  "bot_messages": ["أهلاً وسهلاً..."],
  "bot_paused_until": null,
  "handoff_reason_state": null,
  "facts": {},
  "opt_out_status": "active"
}
```

**Response 200 — skipped (e.g. outside business hours)**
```json
{
  "reply": null,
  "outcome": "skipped",
  "reason": "outside_business_hours",
  "needs_human": false,
  "handoff_reason": null,
  "conversation_id": 1001,
  "turn_index": 1,
  "bot_messages": [],
  "bot_paused_until": null,
  "handoff_reason_state": null,
  "facts": {},
  "opt_out_status": "active"
}
```

**`outcome` values**

| Value | Meaning |
|---|---|
| `delivered` | Reply sent (or would be sent in production) |
| `shadow` | Draft generated but not delivered — awaits agent review |
| `handoff` | Escalated to human agent |
| `skipped` | Turn not processed (see `reason` for why) |
| `failed` | Agent loop exhausted or technical error; fallback message returned |

**`reason` values for `skipped`**

| Value | Meaning |
|---|---|
| `no_config_or_off` | No active `wa_ai_config` found |
| `excluded_number` | Customer phone is in the tenant's excluded-phones list — bot will never engage |
| `outside_business_hours` | Current time is outside configured hours |
| `loop_detected` | Too many bot replies in a short window |
| `lock_contention` | Concurrent message being processed |
| `opted_out` | Customer has opted out |
| `bot_paused` | Human agent took over; bot is paused |
| `duplicate_message` | Turn already processed (idempotency) |
| `pending_transcription` | Audio message awaiting transcription |
| `empty_message` | Message text was empty |
| `greeting_shortcut` | Pure greeting — fast template reply used (counts as `delivered`) |

**`reason` values for `handoff`**

| Value | Meaning |
|---|---|
| `customer_requested_human` | Customer explicitly asked for a human |
| `model_needs_human` | LLM called the `escalate_to_human` tool |
| `citation_violation` | Reply contained bare numbers after one retry |
| `compliance` | Compliance check blocked the turn |
| `media_message` | Non-text media (image/video/document) received |

**`reason` values for `failed`**

| Value | Meaning |
|---|---|
| `budget_exhausted` | Agent loop ran out of steps — graceful fallback returned |
| `provider_error` | LLM API returned an error |
| `loop_failed` | Generic agent loop failure |

**Response 403** — simulating another tenant without admin
```json
{ "error": "Unauthorized." }
```

**Response 422** — missing required field
```json
{ "message": "The wa number id field is required." }
```

**Response 500** — unexpected exception
```json
{ "error": "cURL error 28: Operation timed out after 30000 milliseconds" }
```

---

### 5.2 GET ai/bot/simulate/conversation

Fetch the full transcript of the current sandbox conversation for a given number + phone.

**Query parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `wa_number_id` | integer | yes | WhatsApp number ID |
| `customer_phone` | string | no | Defaults to `+966500000001` |
| `tenant_id` | integer | no | Defaults to authenticated user |

**Response 200**
```json
{
  "conversation_id": 1001,
  "turn_count": 2,
  "messages": [
    { "id": 5001, "direction": "inbound",  "content": "أبحث عن شقة", "status": "received", "segment": null, "outcome": null, "created_at": "2026-08-02T10:00:00+03:00" },
    { "id": 5002, "direction": "outbound", "content": "أهلاً...", "status": "delivered", "segment": null, "outcome": "delivered", "created_at": "2026-08-02T10:00:02+03:00" },
    { "id": 5003, "direction": "inbound",  "content": "وش الأرخص؟", "status": "received", "segment": null, "outcome": null, "created_at": "2026-08-02T10:01:00+03:00" },
    { "id": 5004, "direction": "outbound", "content": "الأرخص هي...", "status": "delivered", "segment": null, "outcome": "delivered", "created_at": "2026-08-02T10:01:03+03:00" }
  ],
  "ai_state": {
    "facts": { "city": "الرياض", "bedrooms": 3 },
    "situation": "عميل يبحث عن شقة في الرياض",
    "requirements": "3 غرف، ميزانية 650 ألف",
    "commitments": null,
    "objections": null,
    "tone": null,
    "opt_out_status": "active",
    "bot_paused_until": null,
    "handoff_reason": null,
    "disclosed_as_assistant": true
  }
}
```

---

### 5.3 POST ai/bot/simulate/reset

Clear the sandbox conversation so a fresh test can begin. Deletes messages, AI state, customer profile, unanswered questions, shadow drafts, and the `Conversation` row. Also clears the loop-guard cache key.

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `wa_number_id` | integer | yes | WhatsApp number ID |
| `customer_phone` | string | no | Defaults to `+966500000001` |
| `tenant_id` | integer | no | Defaults to authenticated user |

**Request example**
```http
POST /api/v1/whatsapp/ai/bot/simulate/reset
Authorization: Bearer {token}
Content-Type: application/json

{
  "wa_number_id": 45,
  "customer_phone": "+966501234567"
}
```

**Response 200 — reset successful**
```json
{
  "success": true,
  "cleared": true,
  "message": "Sandbox conversation reset. You can start a fresh simulation."
}
```

**Response 200 — nothing to reset**
```json
{
  "success": true,
  "cleared": false,
  "message": "No sandbox conversation found — nothing to reset."
}
```

---

## 6. Knowledge Base

Per-tenant knowledge sources power `search_knowledge`. Without indexed sources the retrieval layer stays empty.

### 6.1 GET ai/knowledge

List all knowledge sources for the authenticated tenant.

**Request**
```http
GET /api/v1/whatsapp/ai/knowledge
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "data": [
    {
      "id": 7,
      "type": "faq",
      "name": "رسوم التسجيل",
      "chunk_count": 3,
      "active": true,
      "last_indexed_at": "2026-08-01T12:00:00+00:00",
      "created_at": "2026-08-01T12:00:00+00:00"
    }
  ]
}
```

---

### 6.2 POST ai/knowledge

Create a knowledge source and index its content immediately (embeds + chunk storage).

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | yes | Display name (max 255) |
| `type` | string | yes | `text` \| `faq` \| `property_faq` \| `document` |
| `content` | string | yes | Plain text to index (10–100,000 chars). For file uploads, send extracted text here |
| `active` | boolean | no | Defaults to `true` |

**Request example**
```http
POST /api/v1/whatsapp/ai/knowledge
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "رسوم التسجيل",
  "type": "faq",
  "content": "س: كم رسوم التسجيل في الصكوك؟\nج: رسوم التسجيل تبلغ 1% من قيمة العقار.",
  "active": true
}
```

**Response 201**
```json
{
  "id": 7,
  "name": "رسوم التسجيل",
  "chunk_count": 1,
  "message": "Source indexed successfully."
}
```

**Response 422** — validation error (missing/invalid fields)

---

### 6.3 GET ai/knowledge/{id}

Show a single knowledge source (full model row).

**Path params**

| Param | Type | Description |
|---|---|---|
| `id` | integer | `ai_knowledge_sources.id` |

**Response 200**
```json
{
  "id": 7,
  "user_id": 123,
  "type": "faq",
  "name": "رسوم التسجيل",
  "file_path": null,
  "mime_type": null,
  "chunk_count": 1,
  "embedding_model": "text-embedding-3-small",
  "active": true,
  "last_indexed_at": "2026-08-01T12:00:00+00:00",
  "content_hash": "a1b2c3...",
  "created_at": "2026-08-01T12:00:00+00:00",
  "updated_at": "2026-08-01T12:00:00+00:00"
}
```

**Response 404** — source not found for this tenant

---

### 6.4 PATCH ai/knowledge/{id}

Update name/active flag. If `content` is sent, old chunks are deleted and the source is re-indexed.

**Request body** (all optional; at least one field)

| Field | Type | Description |
|---|---|---|
| `name` | string | New display name |
| `active` | boolean | Enable / disable retrieval for this source |
| `content` | string | New text to re-index (10–100,000 chars) |

**Request example — re-index**
```http
PATCH /api/v1/whatsapp/ai/knowledge/7
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "س: كم رسوم التسجيل؟\nج: 1% من قيمة العقار، تدفع عند الإفراغ."
}
```

**Response 200**
```json
{
  "success": true,
  "chunk_count": 1
}
```

---

### 6.5 DELETE ai/knowledge/{id}

Delete a knowledge source and all of its chunks. Also clears the tenant embedding-matrix cache.

**Response 200**
```json
{
  "success": true
}
```

---

## 7. FAQ Candidates

Auto-mined FAQ drafts for tenant review. Correcting a candidate re-indexes its linked knowledge source when `knowledge_source_id` is set.

### 7.1 GET ai/faq-candidates

Paginated list of FAQ candidates filtered by approval status.

**Query params**

| Param | Type | Default | Description |
|---|---|---|---|
| `status` | string | `auto_approved` | `auto_approved` \| `pending` \| `rejected` |
| `page` | integer | `1` | Laravel paginator page |

**Request**
```http
GET /api/v1/whatsapp/ai/faq-candidates?status=auto_approved
Authorization: Bearer {token}
```

**Response 200** (Laravel paginator envelope)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 41,
      "user_id": 123,
      "cluster_key": "a3f9...",
      "question": "كم رسوم التسجيل في الصكوك؟",
      "drafted_answer": "رسوم التسجيل تبلغ 1% من قيمة العقار.",
      "occurrence_count": 7,
      "approval_status": "auto_approved",
      "knowledge_source_id": 7,
      "mine_batch": "2026-08-01",
      "created_at": "2026-08-01T03:00:00+00:00",
      "updated_at": "2026-08-01T03:00:00+00:00"
    }
  ],
  "per_page": 25,
  "total": 3
}
```

---

### 7.2 PATCH ai/faq-candidates/{id}

Correct the drafted answer and optionally change approval status. If the candidate is linked to a knowledge source, that source is re-indexed with `question + drafted_answer`.

**Path params**

| Param | Type | Description |
|---|---|---|
| `id` | integer | `bot_faq_candidates.id` |

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `drafted_answer` | string | yes | Corrected answer (5–2000 chars) |
| `approval_status` | string | no | `auto_approved` \| `pending` \| `rejected` |

**Request example**
```http
PATCH /api/v1/whatsapp/ai/faq-candidates/41
Authorization: Bearer {token}
Content-Type: application/json

{
  "drafted_answer": "رسوم التسجيل 1% من قيمة العقار، تُدفع عند الإفراغ.",
  "approval_status": "auto_approved"
}
```

**Response 200**
```json
{
  "success": true
}
```

**Response 404** — candidate not found for this tenant

---

## 8. Property External Links

Attach portal listing URLs (Aqar, Bayut, etc.) to internal properties so `resolve_listing` can match inbound portal leads.

### 8.1 GET properties/{propertyId}/external-links

**Path params**

| Param | Type | Description |
|---|---|---|
| `propertyId` | integer | Tenant-owned property ID |

**Request**
```http
GET /api/v1/whatsapp/properties/1301/external-links
Authorization: Bearer {token}
```

**Response 200**
```json
{
  "data": [
    {
      "id": 9,
      "platform": "aqar",
      "url": "https://sa.aqar.fm/ad/6633737/ar",
      "label": "إعلان عقار",
      "active": true,
      "created_at": "2026-08-01T10:00:00+00:00"
    }
  ]
}
```

**Response 404** — property not found for this tenant

---

### 8.2 POST properties/{propertyId}/external-links

**Request body**

| Field | Type | Required | Description |
|---|---|---|---|
| `platform` | string | yes | Portal key (e.g. `aqar`, `bayut`) — max 60 |
| `url` | string | yes | Full listing URL (max 2048). Trailing `/` is stripped |
| `label` | string | no | Optional display label (max 120) |
| `active` | boolean | no | Defaults to `true` |

**Request example**
```http
POST /api/v1/whatsapp/properties/1301/external-links
Authorization: Bearer {token}
Content-Type: application/json

{
  "platform": "aqar",
  "url": "https://sa.aqar.fm/ad/6633737/ar",
  "label": "إعلان عقار"
}
```

**Response 201**
```json
{
  "id": 9,
  "property_id": 1301,
  "user_id": 123,
  "platform": "aqar",
  "url": "https://sa.aqar.fm/ad/6633737/ar",
  "label": "إعلان عقار",
  "active": true,
  "created_at": "2026-08-01T10:00:00+00:00",
  "updated_at": "2026-08-01T10:00:00+00:00"
}
```

---

### 8.3 PATCH properties/{propertyId}/external-links/{linkId}

**Request body** (all optional)

| Field | Type | Description |
|---|---|---|
| `platform` | string | Portal key |
| `url` | string | Listing URL |
| `label` | string \| null | Display label |
| `active` | boolean | Enable / disable for matching |

**Response 200**
```json
{
  "success": true,
  "link": {
    "id": 9,
    "property_id": 1301,
    "platform": "aqar",
    "url": "https://sa.aqar.fm/ad/6633737/ar",
    "label": "إعلان عقار محدّث",
    "active": true
  }
}
```

---

### 8.4 DELETE properties/{propertyId}/external-links/{linkId}

**Response 200**
```json
{
  "success": true
}
```

---

## 9. Flow Diagrams

### 9.1 Complete Bot Turn

```
Customer sends WhatsApp message
            │
            ▼
    [WhatsappAI Module Webhook]
    SyncWhatsappAiConversationToCommunicationService
            │ creates v1 Message + fires MessageReceived event
            ▼
    AutomationEngine::handleMessageReceived()
            │
            ├─ SKIP if: channel ≠ whatsapp
            ├─ SKIP if: source === 'ai'  (loop guard)
            ├─ SKIP if: message already processed (dedup cache 24h)
            ├─ SKIP if: rate limited (5 msgs/60s per conversation)
            │
            │ Check WaAiConfig.autonomy_level ∈ {shadow, autonomous}
            ▼
    Employee::runTurn()
            │
            ├─ GUARD: loop detection (max 3 bot replies/min/conversation)
            ├─ GUARD: WaAiConfig.enabled = true
            ├─ GUARD: within business_hours
            ├─ GUARD: distributed lock (prevents concurrent turn for same conversation)
            ├─ GUARD: WaConversationAiState.opt_out_status ≠ opted_out
            ├─ GUARD: WaConversationAiState.bot_paused = false
            ├─ GUARD: idempotency check (ai_turn_traces)
            ├─ GUARD: message type ≠ audio with pending transcription
            ├─ GUARD: monthly_token_budget not exhausted
            │
            ▼
    ComplianceService::check()
            │
            ├─ opt_out keyword   → send ack, set opted_out, STOP
            ├─ abuse keyword     → send escalation msg, pause bot, STOP
            ├─ regulated topic   → send escalation msg, pause bot, STOP
            ├─ human request     → send "transferring" msg, pause bot, STOP
            └─ pure greeting (returning user) → template reply, STOP (greeting_shortcut)
            │
            ▼
    PersonaComposer::compose()
            │  Builds system prompt: persona + citation rules + brief context
            │  + property context (from FactLedger, initially empty)
            │
            ▼
    AgentLoop::run()    [up to 6 steps, 50s wall-clock]
            │
            │  Each step: LLM decides → structured reply OR tool call
            │
            ├─ Tool: search_inventory(location, property_type, budget, purpose)
            │       → PropertySearchTool → scopeBotAvailable() → max 5 listings
            │       → FactLedger.addProperties()
            │
            ├─ Tool: get_property_details(property_id)
            │       → Fetch full property record + FAQs + external links
            │       → FactLedger.addProperties()
            │
            ├─ Tool: resolve_listing(url | ad_id | attributes)
            │       → ResolveListingTool (portal lead matching)
            │       → URL → PropertyExternalLink → ad_id → attributes
            │
            ├─ Tool: search_knowledge(query)
            │       → EmbeddingService → RetrievalService (cosine sim over KB)
            │       → FactLedger.addKnowledgeChunks()
            │
            ├─ Tool: propose_viewing(property_id, notes)
            │       → Records viewing interest
            │
            ├─ Tool: record_customer_fact(field, value)
            │       → Updates brief directly from conversation
            │
            └─ Tool: escalate_to_human(reason)
                    → FactLedger.recordEscalation()
                    → LLM terminates tool loop and outputs final reply
            │
            ▼ (LLM outputs final structured reply)
            │
    CitationGuard::check()
            │
            ├─ Verify all {{p:ID|field}} placeholders exist in FactLedger
            ├─ Detect bare 4+ digit numbers in 'say' field
            ├─ Detect comma-formatted large numbers (e.g. 7,000,000)
            ├─ Detect availability claims when search returned 0 results
            │
            ├─ VIOLATION → retry once with:
            │       Rebuilt system prompt (now includes FactLedger properties)
            │       + correction instruction (use {{p:ID|field}}, not bare numbers)
            │       + maxSteps = 1
            │
            └─ Still violated after retry → handoff('citation_violation')
            │
            ▼ (guard passed)
            │
    ReplyRenderer::render()
            │  Substitute {{p:ID|field}} → actual property values from FactLedger
            │
            ▼
    BriefMerger::merge()
            │  Merge LLM's brief_updates + tool-recorded facts into CustomerBrief
            │
            ▼
    PolicyGate::evaluate()
            │
            ├─ escalation tool called   → handoff
            ├─ opt_out detected         → opt_out
            ├─ weak search × 3 turns   → low_confidence_soft (deliver + track)
            └─ normal                   → deliver
            │
            ▼
    Route by decision:
            │
            ├─ handoff / opt_out
            │       → HumanCadence::send() (escalation message)
            │       → HandoffService::pauseBot()
            │
            ├─ shadow (autonomy_level = 'shadow')
            │       → ShadowBotDraft::create()
            │       → no message sent to customer
            │
            └─ deliver (autonomy_level = 'autonomous')
                    → HumanCadence::send()
                            ├─ Human-like typing delay
                            └─ WhatsAppChannelSender (Meta Cloud API)
            │
            ▼ (post-turn)
            │
            ├─ Update WaConversationAiState.facts + last_bot_reply_at
            ├─ Record ai_turn_traces (telemetry + idempotency key)
            ├─ Record ai_usage_logs (token counts)
            ├─ If ≥ 8 new turns since last summary:
            │       → SummarizeConversationJob (queue: ai)
            └─ CrmFlywheelService::sync()
                    → upsert api_customers (by phone)
                    → create users_property_requests (if intent + location known)
```

---

### 9.2 Shadow Mode Lifecycle

```
                                    BOT TURN COMPLETES
                                           │
                              autonomy_level = 'shadow'
                                           │
                                           ▼
                              ShadowBotDraft created
                              status = 'pending'
                              confidence = 87
                              draft_reply = "لدينا شقة..."
                              used_sources = [1301, 1302]   ← property IDs
                                           │
                         ┌─────────────────┼─────────────────┐
                         ▼                 ▼                 ▼
                    APPROVE           EDIT + SEND        DISCARD
                         │                 │                 │
               status='approved'    status='edited'   status='discarded'
                                    agent_reply=<text>
                                           │
                                     ┌─────┘
                                     ▼
                              Agent sends reply
                              manually from inbox
                                     │
                              HandoffService
                              pauses bot 48h
                              (agent takeover)

Edit-rate = (edited + discarded) / total × 100%
Target: < 15% before graduating to autonomous
```

---

### 9.3 Simulator Flow

```
POST /api/v1/whatsapp/ai/bot/simulate
{
  "wa_number_id": 45,
  "message": "أبحث عن شقة في النزهة",
  "customer_phone": "+966501234567"
}
        │
        ▼
SandboxService::conversationFor()
  └─ Conversation.firstOrCreate(channel='whatsapp_sandbox')
        │
        ▼
Message::create(direction='inbound')  ← no MessageReceived event fired
        │
        ▼
Employee::runTurn(..., dryRun=true)
   ├─ Loop guard: SKIPPED (dryRun)
   ├─ Distributed lock: SKIPPED (dryRun)
   ├─ Idempotency write: SKIPPED (dryRun)
   ├─ Business hours: ENFORCED (returns skipped if outside hours)
   ├─ WaConversationAiState::firstOrCreate()
   ├─ ComplianceService::check()        ← real
   ├─ AgentLoop (tool-calling)          ← real LLM calls
   │    ├─ search_inventory             ← real DB queries
   │    ├─ search_knowledge             ← real vector search
   │    └─ other tools as needed
   ├─ CitationGuard::check()            ← real (retry on violation)
   ├─ ReplyRenderer::render()           ← real (substitutes placeholders)
   ├─ PolicyGate::evaluate()            ← real
   └─ Returns EmployeeTurnResult (no HumanCadence call)
        │
        ▼
Message::create(direction='outbound')  ← no provider send
        │
        ▼
Response JSON ← NOTHING SENT TO WHATSAPP
{
  reply, outcome, reason, needs_human, handoff_reason,
  conversation_id, turn_index, bot_messages[],
  bot_paused_until, handoff_reason_state,
  facts, opt_out_status
}

POST /api/v1/whatsapp/ai/bot/simulate/reset
        │
        ▼
DB::transaction {
  DELETE messages WHERE conversation_id = $id
  DELETE wa_conversation_ai_states WHERE conversation_id = $id
  DELETE shadow_bot_drafts WHERE conversation_id = $id
  DELETE bot_unanswered_questions WHERE conversation_id = $id
  DELETE ai_customer_profiles WHERE user_id = $tenantId AND phone = $sandboxPhone
  DELETE conversations WHERE id = $id
}
Cache::forget('bot.loop.conv.' . $id)
```

> The simulator uses **real tenant data** (KB, properties, conversation history persisted across turns). Token usage is logged in `ai_usage_logs` but no monthly budget is deducted in dry-run mode.

---

## 10. Error Reference

| HTTP | Code | When |
|---|---|---|
| 400 | — | Malformed JSON body |
| 401 | — | Missing or invalid Bearer token |
| 403 | `Unauthorized.` | Simulating a different tenant without admin |
| 404 | `WA_NUMBER_NOT_FOUND` | `numberId` not owned by this tenant |
| 404 | `WA_AI_CONFIG_NOT_FOUND` | No config exists for this number yet |
| 404 | `NOT_FOUND` | Excluded-phone record not found for this number |
| 404 | `WA_CONVERSATION_NOT_FOUND` | Conversation ID not found (bot/resume) |
| 404 | `No bot config found...` | Simulate: no `wa_ai_configs` row for this number |
| 404 | (ModelNotFound) | Knowledge source, FAQ candidate, property, or external link not found for tenant |
| 409 | `Draft is no longer pending.` | Act on a draft that was already approved/discarded |
| 422 | `PHONE_ALREADY_EXCLUDED` | Phone already in the exclusion list (POST excluded-phones) |
| 422 | `INVALID_PHONE` | Phone contained no digits after normalization |
| 422 | `BOT_PAUSE_NOT_RESUMABLE` | Attempting to resume a bot that was paused for a non-takeover reason |
| 422 | (Laravel validation) | Required field missing or invalid value |
| 500 | (exception message) | LLM timeout, network failure, or unexpected error |

### Handoff reasons (appear in `reason` / `handoff_reason` fields)

| Reason | Trigger |
|---|---|
| `customer_requested_human` | Customer sent a human-request keyword |
| `model_needs_human` | LLM called the `escalate_to_human` tool |
| `citation_violation` | Reply still contained bare numbers after one LLM retry |
| `compliance` | Regulated topic or abuse keyword detected |
| `media_message` | Non-text media received (image/video/document) |
| `loop_failed:budget_exhausted` | Agent exhausted step limit (6 steps); graceful fallback delivered |
| `loop_failed:provider_error` | LLM API returned an error |

### Skipped reasons (appear in `reason` when `outcome = "skipped"`)

| Reason | Trigger |
|---|---|
| `no_config_or_off` | No enabled `wa_ai_config` for this number |
| `excluded_number` | Customer phone is in the tenant's excluded-phones list |
| `outside_business_hours` | Current time outside configured hours |
| `loop_detected` | More than 3 bot replies in 1 minute for this conversation |
| `lock_contention` | Another turn is being processed concurrently |
| `opted_out` | Customer opted out |
| `bot_paused` | Human agent took over |
| `duplicate_message` | Turn already processed (idempotency key match) |
| `pending_transcription` | Audio message is still being transcribed |
| `empty_message` | Message body was blank |

### Rate limiting (AutomationEngine internal)
The bot will not fire more than **5 times per 60 seconds** per conversation. If a customer sends a burst, subsequent messages within the window are silently skipped. This is enforced in `AutomationEngine`, not at the HTTP layer.

### Loop guard
The bot will not send more than **3 auto-replies per minute** per conversation. Excess turns are logged as `agent.employee.loop_detected` and skipped.
