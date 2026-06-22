# Roles API Documentation

Base URL: `/roles`

Semua endpoint roles menggunakan middleware `EnsureApiToken`.

Role disimpan pada tabel `authentication.roles`. Role bersifat CRUD penuh — bisa tambah, edit nama, dan hapus (jika tidak memiliki user).

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/roles` | EnsureApiToken | List role |
| `POST` | `/roles/store` | EnsureApiToken | Tambah role baru |
| `GET` | `/roles/{guid}` | EnsureApiToken | Detail role |
| `PUT` | `/roles/update` | EnsureApiToken | Update role |
| `DELETE` | `/roles/{guid}` | EnsureApiToken | Hapus role |

---

## 1. List Roles — `POST /roles`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "uuid-role",
        "set_name": true,
        "name": "Cashier",
        "set_is_default": false,
        "is_default": false
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
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:name,is_default,created_at,updated_at |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "uuid-role",
                "name": "Cashier",
                "is_default": false,
                "created_at": "2026-06-03T00:00:00.000000Z",
                "updated_at": "2026-06-03T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Role — `POST /roles/store`

### Request Body

```json
{
    "name": "Supervisor",
    "is_default": false
}
```

### Validation

| Field | Rule |
|---|---|
| `name` | required, string, max:100, unique |
| `is_default` | nullable, boolean (default: false) |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-role",
            "name": "Supervisor",
            "is_default": false,
            "created_at": "2026-06-22T12:00:00.000000Z",
            "updated_at": "2026-06-22T12:00:00.000000Z"
        },
        "message_en": "Role created.",
        "message_id": "Role berhasil dibuat."
    }
}
```

---

## 3. Show Role — `GET /roles/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-role",
            "name": "Cashier",
            "is_default": false,
            "created_at": "2026-06-03T00:00:00.000000Z",
            "updated_at": "2026-06-03T00:00:00.000000Z"
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
        "message_en": "Role not found.",
        "message_id": "Role tidak ditemukan."
    }
}
```

---

## 4. Update Role — `PUT /roles/update`

### Request Body

```json
{
    "guid": "uuid-role",
    "name": "Senior Cashier",
    "is_default": false
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `name` | required, string, max:100, unique (ignore self) |
| `is_default` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-role",
            "name": "Senior Cashier",
            "is_default": false,
            "created_at": "2026-06-03T00:00:00.000000Z",
            "updated_at": "2026-06-22T12:30:00.000000Z"
        },
        "message_en": "Role updated.",
        "message_id": "Role berhasil diperbarui."
    }
}
```

---

## 5. Delete Role — `DELETE /roles/{guid}`

Menghapus role dari database. Gagal jika role masih memiliki user.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Role deleted.",
        "message_id": "Role berhasil dihapus."
    }
}
```

### Error — Conflict (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Role has users assigned, cannot delete.",
        "message_id": "Role masih memiliki user, tidak dapat dihapus."
    }
}
```

---

## Data Structures

### Role Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `name` | string | Nama role |
| `is_default` | boolean | Role default untuk registrasi baru |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Fixed Role List (Seeder)

| Role | Default | Deskripsi |
|---|---|---|
| `Superadmin` | false | Full dashboard + API |
| `Owner` | false | Laporan + KPI |
| `Manager` | false | Laporan, shift, katalog |
| `Cashier` | false | Tablet POS API |
| `Users` | true | Default registrasi, no akses |
