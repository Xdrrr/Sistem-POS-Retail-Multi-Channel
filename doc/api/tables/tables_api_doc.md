# Tables API Documentation

Base URL: `/tables`

Semua endpoint tables menggunakan middleware `EnsureApiToken`.

Master data meja restoran disimpan pada tabel `orders.tables`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/tables` | EnsureApiToken | List meja |
| `POST` | `/tables/store` | EnsureApiToken | Tambah meja baru |
| `GET` | `/tables/{guid}` | EnsureApiToken | Detail meja |
| `PUT` | `/tables/update` | EnsureApiToken | Update meja |
| `DELETE` | `/tables/{guid}` | EnsureApiToken | Nonaktifkan meja |
| `GET` | `/tables/status/all` | EnsureApiToken | Status real-time semua meja |

---

## 1. List Tables — `POST /tables`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "set_table_number": false,
        "table_number": "A1",
        "set_location": true,
        "location": "indoor",
        "set_status": false,
        "status": "available",
        "set_guid_cabang": false
    },
    "limit": 20,
    "page": 1,
    "order": "table_number",
    "sort": "ASC"
}
```

### Validation

| Field | Rule |
|---|---|
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:table_number,capacity,location,status,created_at |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "uuid-table",
                "table_number": "A1",
                "capacity": 4,
                "location": "indoor",
                "status": "available",
                "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
                "is_active": true,
                "created_at": "2026-06-17T00:00:00.000000Z",
                "updated_at": "2026-06-17T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Table — `POST /tables/store`

### Request Body

```json
{
    "table_number": "C1",
    "capacity": 8,
    "location": "indoor",
    "status": "available",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001"
}
```

### Validation

| Field | Rule |
|---|---|
| `table_number` | required, string, max:30, unique |
| `capacity` | nullable, integer, min:1 (default: 4) |
| `location` | nullable, string, in:indoor,outdoor (default: indoor) |
| `status` | nullable, string, in:available,maintenance (default: available) |
| `guid_cabang` | nullable, string |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-table",
            "table_number": "C1",
            "capacity": 8,
            "location": "indoor",
            "status": "available",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T12:00:00.000000Z",
            "updated_at": "2026-06-17T12:00:00.000000Z"
        },
        "message_en": "Table created.",
        "message_id": "Meja berhasil dibuat."
    }
}
```

---

## 3. Show Table — `GET /tables/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-table",
            "table_number": "A1",
            "capacity": 4,
            "location": "indoor",
            "status": "available",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T00:00:00.000000Z",
            "updated_at": "2026-06-17T00:00:00.000000Z"
        }
    }
}
```

---

## 4. Update Table — `PUT /tables/update`

### Request Body

```json
{
    "guid": "uuid-table",
    "table_number": "A1",
    "capacity": 6,
    "location": "outdoor",
    "status": "maintenance",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists:orders.tables,guid |
| `table_number` | nullable, string, max:30, unique (ignore self) |
| `capacity` | nullable, integer, min:1 |
| `location` | nullable, string, in:indoor,outdoor |
| `status` | nullable, string, in:available,occupied,reserved,maintenance |
| `guid_cabang` | nullable, string |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-table",
            "table_number": "A1",
            "capacity": 6,
            "location": "outdoor",
            "status": "maintenance",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T00:00:00.000000Z",
            "updated_at": "2026-06-17T12:30:00.000000Z"
        },
        "message_en": "Table updated.",
        "message_id": "Meja berhasil diperbarui."
    }
}
```

---

## 5. Delete Table — `DELETE /tables/{guid}`

Soft delete (`is_active = false`).

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Table deactivated.",
        "message_id": "Meja dinonaktifkan."
    }
}
```

---

## 6. Status All Tables — `GET /tables/status/all`

Mengembalikan status real-time semua meja aktif. Status dihitung otomatis:

| Status | Kondisi |
|---|---|
| `available` | Tidak ada order/reservasi aktif |
| `occupied` | Ada order dengan status `open` |
| `reserved` | Ada reservasi `pending`/`confirmed` hari ini |
| `maintenance` | Status manual dari admin |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "uuid-table",
                "table_number": "A1",
                "capacity": 4,
                "location": "indoor",
                "status": "available",
                "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
                "is_active": true,
                "created_at": "2026-06-17T00:00:00.000000Z",
                "updated_at": "2026-06-17T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## Data Structures

### Table Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `table_number` | string | Nomor meja |
| `capacity` | integer | Kapasitas tamu |
| `location` | string | `indoor` / `outdoor` |
| `status` | string | `available` / `occupied` / `reserved` / `maintenance` |
| `guid_cabang` | string (UUID) | GUID cabang |
| `is_active` | boolean | Status aktif |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |
