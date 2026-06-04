# Groups (Product Groups) API Documentation

Base URL: `/groups`

Semua endpoint groups menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/groups` | EnsureApiToken | List grup produk |
| `POST` | `/groups/store` | EnsureApiToken | Tambah grup baru |
| `GET` | `/groups/{guid}` | EnsureApiToken | Detail grup |
| `PUT` | `/groups/update` | EnsureApiToken | Update grup |
| `DELETE` | `/groups/{guid}` | EnsureApiToken | Hapus grup |

---

## 1. List Groups — `POST /groups`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "22222222-2222-4222-8222-000000000001",
        "set_is_active": true,
        "is_active": true
    },
    "limit": 20,
    "page": 1,
    "order": "name",
    "sort": "ASC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:name,description,is_active,created_at,updated_at (default: name) |
| `sort` | nullable, string, in:ASC,DESC (default: ASC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "22222222-2222-4222-8222-000000000001",
                "name": "kopi",
                "description": null,
                "image": null,
                "image_url": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            },
            {
                "guid": "22222222-2222-4222-8222-000000000002",
                "name": "nasi",
                "description": null,
                "image": null,
                "image_url": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Group — `POST /groups/store`

### Request Body

```json
{
    "name": "susu",
    "description": "Kelompok produk susu",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `name` | required, string, max:100, unique |
| `description` | nullable, string |
| `image` | nullable, image file |
| `is_active` | nullable, boolean |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "22222222-2222-4222-8222-000000000011",
            "name": "susu",
            "description": "Kelompok produk susu",
            "image": null,
            "image_url": null,
            "is_active": true,
            "created_at": "2026-06-04T12:00:00.000000Z",
            "updated_at": "2026-06-04T12:00:00.000000Z"
        },
        "message_en": "Group created successfully.",
        "message_id": "Group berhasil dibuat."
    }
}
```

---

## 3. Show Group — `GET /groups/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "22222222-2222-4222-8222-000000000001",
            "name": "kopi",
            "description": null,
            "image": null,
            "image_url": null,
            "is_active": true,
            "created_at": "2026-06-04T00:00:00.000000Z",
            "updated_at": "2026-06-04T00:00:00.000000Z"
        }
    }
}
```

### Error — Not Found (404)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Group not found.",
        "message_id": "Group tidak ditemukan."
    }
}
```

---

## 4. Update Group — `PUT /groups/update`

### Request Body

```json
{
    "guid": "22222222-2222-4222-8222-000000000001",
    "name": "kopi specialty",
    "description": "Kelompok kopi specialty",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `name` | required, string, max:100, unique (ignore self) |
| `description` | nullable, string |
| `image` | nullable, image file |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "22222222-2222-4222-8222-000000000001",
            "name": "kopi specialty",
            "description": "Kelompok kopi specialty",
            "image": null,
            "image_url": null,
            "is_active": true,
            "created_at": "2026-06-04T00:00:00.000000Z",
            "updated_at": "2026-06-04T12:05:00.000000Z"
        },
        "message_en": "Group updated successfully.",
        "message_id": "Group berhasil diperbarui."
    }
}
```

---

## 5. Delete Group — `DELETE /groups/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Group deleted successfully.",
        "message_id": "Group berhasil dihapus."
    }
}
```

### Error — Used by Products (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Group is used by product data.",
        "message_id": "Group masih digunakan oleh data produk."
    }
}
```

---

## Data Structures

### Group Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `name` | string | Nama grup |
| `description` | string or null | Deskripsi |
| `image` | string or null | Path gambar |
| `image_url` | string or null | URL gambar |
| `is_active` | boolean | Status aktif |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Static GUIDs (from CatalogSeeder)

| Group | GUID |
|---|---|
| kopi | `22222222-2222-4222-8222-000000000001` |
| nasi | `22222222-2222-4222-8222-000000000002` |
| mi | `22222222-2222-4222-8222-000000000003` |
| pasta | `22222222-2222-4222-8222-000000000004` |
| ayam | `22222222-2222-4222-8222-000000000005` |
| teh | `22222222-2222-4222-8222-000000000006` |
| jus | `22222222-2222-4222-8222-000000000007` |
| roti | `22222222-2222-4222-8222-000000000008` |
| kue | `22222222-2222-4222-8222-000000000009` |
| combo | `22222222-2222-4222-8222-000000000010` |
