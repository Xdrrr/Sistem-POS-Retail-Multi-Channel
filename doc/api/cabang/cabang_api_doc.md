# Cabang API Documentation

Base URL: `/cabang`

Semua endpoint cabang menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/cabang` | EnsureApiToken | List cabang |
| `POST` | `/cabang/store` | EnsureApiToken | Tambah cabang baru |
| `GET` | `/cabang/{guid}` | EnsureApiToken | Detail cabang |
| `PUT` | `/cabang/update` | EnsureApiToken | Update cabang |
| `DELETE` | `/cabang/{guid}` | EnsureApiToken | Nonaktifkan cabang |

---

## 1. List Cabang — `POST /cabang`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "aaaaaaaa-aaaa-4000-8000-000000000001",
        "set_kode": true,
        "kode": "PUSAT",
        "set_is_active": true,
        "is_active": true
    },
    "limit": 20,
    "page": 1,
    "order": "kode",
    "sort": "ASC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_kode` | nullable, boolean |
| `filter.kode` | nullable, string |
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:kode,nama,is_active,created_at,updated_at (default: kode) |
| `sort` | nullable, string, in:ASC,DESC (default: ASC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "aaaaaaaa-aaaa-4000-8000-000000000001",
                "kode": "PUSAT",
                "nama": "Pusat",
                "alamat": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            },
            {
                "guid": "aaaaaaaa-aaaa-4000-8000-000000000002",
                "kode": "CBG1",
                "nama": "Cabang 1",
                "alamat": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Cabang — `POST /cabang/store`

### Request Body

```json
{
    "kode": "CBG3",
    "nama": "Cabang 3",
    "alamat": "Jl. Merdeka No. 123",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `kode` | required, string, max:50, unique |
| `nama` | required, string, max:100 |
| `alamat` | nullable, string |
| `is_active` | nullable, boolean (default: true) |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "aaaaaaaa-aaaa-4000-8000-000000000004",
            "kode": "CBG3",
            "nama": "Cabang 3",
            "alamat": "Jl. Merdeka No. 123",
            "is_active": true,
            "created_at": "2026-06-04T12:00:00.000000Z",
            "updated_at": "2026-06-04T12:00:00.000000Z"
        },
        "message_en": "Branch created successfully.",
        "message_id": "Cabang berhasil dibuat."
    }
}
```

---

## 3. Show Cabang — `GET /cabang/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "kode": "PUSAT",
            "nama": "Pusat",
            "alamat": null,
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
        "message_en": "Branch not found.",
        "message_id": "Cabang tidak ditemukan."
    }
}
```

---

## 4. Update Cabang — `PUT /cabang/update`

### Request Body

```json
{
    "guid": "aaaaaaaa-aaaa-4000-8000-000000000001",
    "kode": "PUSAT",
    "nama": "Pusat",
    "alamat": "Jl. Pusat No. 1",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `kode` | required, string, max:50, unique (ignore self) |
| `nama` | required, string, max:100 |
| `alamat` | nullable, string |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "kode": "PUSAT",
            "nama": "Pusat",
            "alamat": "Jl. Pusat No. 1",
            "is_active": true,
            "created_at": "2026-06-04T00:00:00.000000Z",
            "updated_at": "2026-06-04T12:05:00.000000Z"
        },
        "message_en": "Branch updated successfully.",
        "message_id": "Cabang berhasil diperbarui."
    }
}
```

---

## 5. Delete Cabang — `DELETE /cabang/{guid}`

> Soft delete: mengubah `is_active` menjadi `false`.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Branch deactivated successfully.",
        "message_id": "Cabang berhasil dinonaktifkan."
    }
}
```

---

## Data Structures

### Cabang Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `kode` | string | Kode cabang |
| `nama` | string | Nama cabang |
| `alamat` | string or null | Alamat cabang |
| `is_active` | boolean | Status aktif |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Static Data (from CabangSeeder)

| Kode | Nama | GUID |
|---|---|---|
| PUSAT | Pusat | `aaaaaaaa-aaaa-4000-8000-000000000001` |
| CBG1 | Cabang 1 | `aaaaaaaa-aaaa-4000-8000-000000000002` |
| CBG2 | Cabang 2 | `aaaaaaaa-aaaa-4000-8000-000000000003` |
