# Building API – Frontend Integration Guide

This document describes the **Building** API for the frontend team, including support for **multiple water and electricity meter numbers** per building.

---

## Base URL & Authentication

- **Base URL:** `{API_BASE}/api`
- **Authentication:** All building endpoints require **Bearer token** (Sanctum).
- **Header:** `Authorization: Bearer {token}`

---

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/buildings` | List buildings (paginated) |
| `GET` | `/buildings/{id}` | Get single building |
| `POST` | `/buildings` | Create building |
| `PUT` | `/buildings/{id}` | Update building |
| `DELETE` | `/buildings/{id}` | Delete building |
| `POST` | `/buildings/upload-image` | Upload building image |
| `POST` | `/buildings/upload-deed-image` | Upload deed image |

---

## Data Model

### Building Object

Each building can have **multiple water meters** and **multiple electricity meters**. They are returned in the `meters` array.

| Field | Type | Description |
|-------|------|-------------|
| `id` | number | Building ID |
| `name` | string | Building name |
| `image` | string \| null | Image path |
| `image_url` | string \| null | Full image URL (appended) |
| `deed_number` | string \| null | Deed number |
| `deed_image` | string \| null | Deed image path |
| `deed_image_url` | string \| null | Full deed image URL (appended) |
| `user_id` | number | Owner user ID |
| `meters` | array | List of meter records (see below) |
| `properties` | array | Related properties (when loaded) |
| `created_at` | string (ISO 8601) | Created at |
| `updated_at` | string (ISO 8601) | Updated at |

### Meter Object (inside `building.meters`)

| Field | Type | Description |
|-------|------|-------------|
| `id` | number | Meter record ID |
| `building_id` | number | Building ID |
| `meter_type` | string | `"water"` or `"electricity"` |
| `meter_number` | string | Meter number |
| `created_at` | string (ISO 8601) | Created at |
| `updated_at` | string (ISO 8601) | Updated at |

**Meter type values:**

- `water` – Water meter
- `electricity` – Electricity meter

---

## 1. List Buildings

**Request**

```http
GET /api/buildings?search={optional}&per_page=15
Authorization: Bearer {token}
```

**Query parameters**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Filter by building name (partial match) |
| `per_page` | number | 15 | Items per page |

**Response (200)**

```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Tower A",
        "image": "buildings/example.jpg",
        "image_url": "https://example.com/buildings/example.jpg",
        "deed_number": "DEED-001",
        "deed_image": "buildings/deeds/deed1.pdf",
        "deed_image_url": "https://example.com/buildings/deeds/deed1.pdf",
        "user_id": 1,
        "created_at": "2026-02-04T12:00:00.000000Z",
        "updated_at": "2026-02-04T12:00:00.000000Z",
        "meters": [
          {
            "id": 1,
            "building_id": 1,
            "meter_type": "water",
            "meter_number": "W-001",
            "created_at": "2026-02-04T12:00:00.000000Z",
            "updated_at": "2026-02-04T12:00:00.000000Z"
          },
          {
            "id": 2,
            "building_id": 1,
            "meter_type": "electricity",
            "meter_number": "E-001",
            "created_at": "2026-02-04T12:00:00.000000Z",
            "updated_at": "2026-02-04T12:00:00.000000Z"
          }
        ],
        "properties": []
      }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "links": [],
    "next_page_url": null,
    "path": "/api/buildings",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

---

## 2. Get Single Building

**Request**

```http
GET /api/buildings/{id}
Authorization: Bearer {token}
```

**Response (200)**

Same building object as in the list (with `meters` and `properties` when loaded).

**Response (404)**

```json
{
  "status": "error",
  "message": "Building not found"
}
```

---

## 3. Create Building

**Request**

```http
POST /api/buildings
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (JSON)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Building name (max 255) |
| `deed_number` | string | No | Deed number (max 255) |
| `image` | string | No | Image path (if already uploaded) |
| `deed_image` | string | No | Deed image path (if already uploaded) |
| `water_meter_numbers` | string[] | No | Array of water meter numbers |
| `electricity_meter_numbers` | string[] | No | Array of electricity meter numbers |

**Example**

```json
{
  "name": "Tower B",
  "deed_number": "DEED-002",
  "water_meter_numbers": ["W-101", "W-102"],
  "electricity_meter_numbers": ["E-101", "E-102"]
}
```

- You can send **no meters** (omit or empty arrays).
- Each array can have **multiple** values.
- Empty strings in the arrays are ignored.

**Response (201)**

```json
{
  "status": "success",
  "message": "Building created successfully",
  "data": {
    "id": 2,
    "name": "Tower B",
    "image": null,
    "image_url": null,
    "deed_number": "DEED-002",
    "deed_image": null,
    "deed_image_url": null,
    "user_id": 1,
    "created_at": "2026-02-04T12:00:00.000000Z",
    "updated_at": "2026-02-04T12:00:00.000000Z",
    "user": { "id": 1, "username": "...", "email": "..." },
    "meters": [
      { "id": 3, "building_id": 2, "meter_type": "water", "meter_number": "W-101", "created_at": "...", "updated_at": "..." },
      { "id": 4, "building_id": 2, "meter_type": "water", "meter_number": "W-102", "created_at": "...", "updated_at": "..." },
      { "id": 5, "building_id": 2, "meter_type": "electricity", "meter_number": "E-101", "created_at": "...", "updated_at": "..." },
      { "id": 6, "building_id": 2, "meter_type": "electricity", "meter_number": "E-102", "created_at": "...", "updated_at": "..." }
    ]
  }
}
```

**Validation error (422)**

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## 4. Update Building

**Request**

```http
PUT /api/buildings/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (JSON)**  
Same fields as create. All meter numbers are **replaced** by the submitted arrays:

- If you send `water_meter_numbers: ["W-201"]`, only that one water meter remains; previous water meters are removed.
- **Important:** If you omit `water_meter_numbers` or `electricity_meter_numbers`, they are treated as empty arrays, so those meter types are **cleared**. To keep existing meters on update, always send the full list you want (e.g. load building, then send back the same arrays plus any new/removed values). To clear all meters, send `water_meter_numbers: []`, `electricity_meter_numbers: []`.

**Example (replace meters)**

```json
{
  "name": "Tower B Updated",
  "deed_number": "DEED-002",
  "water_meter_numbers": ["W-201"],
  "electricity_meter_numbers": ["E-201", "E-202"]
}
```

**Response (200)**

```json
{
  "status": "success",
  "message": "Building updated successfully",
  "data": {
    "id": 2,
    "name": "Tower B Updated",
    "user_id": 1,
    "meters": [ ... ],
    "user": { ... }
  }
}
```

---

## 5. Delete Building

**Request**

```http
DELETE /api/buildings/{id}
Authorization: Bearer {token}
```

**Response (200)**  
Building has no linked properties/rentals:

```json
{
  "status": "success",
  "message": "Building deleted successfully"
}
```

**Response (422)**  
Building has linked properties or rentals:

```json
{
  "status": "error",
  "message": "Cannot delete building. It has properties linked to it."
}
```

---

## 6. Upload Building Image

**Request**

```http
POST /api/buildings/upload-image
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `image` | file | Yes | Image file (jpg, jpeg, png; max 5MB) |

**Response (200)**

```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "data": {
    "path": "buildings/building_123456_abc.jpg",
    "url": "https://example.com/buildings/building_123456_abc.jpg",
    "filename": "building_123456_abc.jpg"
  }
}
```

Use `data.path` or `data.url` in create/update building as `image`.

---

## 7. Upload Deed Image

**Request**

```http
POST /api/buildings/upload-deed-image
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (form-data)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `deed_image` | file | Yes | Deed file (jpg, jpeg, png, pdf; max 5MB) |

**Response (200)**  
Same shape as upload building image; use returned `path` or `url` as `deed_image` in create/update.

---

## Form-Data (Create/Update)

Create and update also accept **multipart/form-data** instead of JSON:

- `name` (required), `deed_number`, `image` (file), `deed_image` (file).
- For **multiple meter numbers** with form-data you can send repeated keys, for example:
  - `water_meter_numbers[]=W-001`
  - `water_meter_numbers[]=W-002`
  - `electricity_meter_numbers[]=E-001`
- Or send JSON for nested fields if your client supports it. Prefer **JSON** for clarity when sending arrays.

---

## Frontend Checklist

1. **List/Detail:** Use `building.meters` array; split by `meter_type` for water vs electricity if needed.
2. **Create/Edit form:**  
   - Send `water_meter_numbers` and `electricity_meter_numbers` as arrays of strings.  
   - Allow adding/removing multiple entries per type.
3. **Update behavior:** Submitting an update **replaces** all meters with the sent arrays. Always send the full `water_meter_numbers` and `electricity_meter_numbers` you want to keep (omit = clear). Send empty arrays to clear all meters.
4. **Property response:** When a property includes a `building` relation, that building object includes a `meters` array (same shape as above).

---

## Changelog (for frontend)

- **Building** no longer has a single `water_meter_number` field.
- Building has **no** single `electricity_meter_number` field; electricity is only via `meters`.
- New: **`meters`** array on building with objects `{ id, building_id, meter_type, meter_number, created_at, updated_at }`.
- Create/Update: use **`water_meter_numbers`** (array) and **`electricity_meter_numbers`** (array) to set multiple meters per building.
