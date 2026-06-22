# Users API Documentation

Base URL: `/users`

Semua endpoint users menggunakan middleware `EnsureApiToken`.

User disimpan pada tabel `authentication.users` dengan relasi ke `authentication.user_details` (nama, email, telepon, dll) dan `authentication.roles`.

Password di-hash dengan SHA-256 + salt (base64).

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/users` | EnsureApiToken | List user dengan search + filter |
| `POST` | `/users/store` | EnsureApiToken | Tambah user baru |
| `GET` | `/users/{guid}` | EnsureApiToken | Detail user |
| `PUT` | `/users/update` | EnsureApiToken | Update user |
| `DELETE` | `/users/{guid}` | EnsureApiToken | Nonaktifkan user |

---

## 1. List Users — `POST /users`

### Request Body (with search + filter)

```json
{
    "search": "budi",
    "filter": {
        "set_guid": false,
        "guid": "uuid-user",
        "set_role_name": true,
        "role_name": "Cashier",
        "set_guid_cabang": false,
        "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
        "set_is_active": true,
        "is_active": true
    },
    "limit": 20,
    "page": 1,
    "order": "created_at",
    "sort": "DESC"
}
```

### Validation

| Field | Rule |
|---|---|
| `search` | nullable, string, max:100 (ILIKE username, full_name, email) |
| `filter.set_guid` / `filter.guid` | nullable, boolean / string |
| `filter.set_role_name` / `filter.role_name` | nullable, boolean / string |
| `filter.set_guid_cabang` / `filter.guid_cabang` | nullable, boolean / string |
| `filter.set_is_active` / `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:username,full_name,email,role_name,created_at |
| `sort` | nullable, string, in:ASC,DESC (default: DESC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "items": [
                {
                    "guid": "uuid-user",
                    "username": "kasir1@wit.id",
                    "role": {
                        "guid": "uuid-role",
                        "name": "Cashier"
                    },
                    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
                    "is_active": true,
                    "detail": {
                        "full_name": "Kasir Satu",
                        "email": "kasir1@wit.id",
                        "phone_number": "08123456789",
                        "gender": "",
                        "address": null,
                        "city": null,
                        "province": null
                    },
                    "url_image": "",
                    "last_login": "2026-06-22T08:00:00.000000Z",
                    "created_at": "2026-06-20T10:00:00.000000Z",
                    "updated_at": "2026-06-22T08:00:00.000000Z"
                }
            ],
            "total": 1
        }
    }
}
```

---

## 2. Create User — `POST /users/store`

### Request Body

```json
{
    "username": "kasir2@wit.id",
    "password": "rahasia123",
    "confirm_password": "rahasia123",
    "role_guid": "uuid-role-cashier",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
    "full_name": "Kasir Dua",
    "email": "kasir2@wit.id",
    "phone_number": "08123456788",
    "gender": "Laki-laki",
    "address": "Jl. Merdeka No. 1",
    "city": "Jakarta",
    "province": "DKI Jakarta",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `username` | required, email, max:255, unique |
| `password` | required, string, min:6 |
| `confirm_password` | required, same:password |
| `role_guid` | required, string, exists:authentication.roles.guid |
| `guid_cabang` | nullable, string, exists:authentication.cabang.guid (default: PUSAT) |
| `full_name` | required, string, max:255 |
| `email` | nullable, email, max:255 |
| `phone_number` | nullable, string, max:50 |
| `gender` | nullable, string, max:30 |
| `address` | nullable, string |
| `city` | nullable, string, max:255 |
| `province` | nullable, string, max:255 |
| `is_active` | nullable, boolean (default: true) |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-user",
            "username": "kasir2@wit.id",
            "role": {
                "guid": "uuid-role-cashier",
                "name": "Cashier"
            },
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "detail": {
                "full_name": "Kasir Dua",
                "email": "kasir2@wit.id",
                "phone_number": "08123456788",
                "gender": "Laki-laki",
                "address": "Jl. Merdeka No. 1",
                "city": "Jakarta",
                "province": "DKI Jakarta"
            },
            "url_image": "",
            "last_login": null,
            "created_at": "2026-06-22T12:00:00.000000Z",
            "updated_at": "2026-06-22T12:00:00.000000Z"
        },
        "message_en": "User created.",
        "message_id": "User berhasil dibuat."
    }
}
```

---

## 3. Show User — `GET /users/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-user",
            "username": "kasir1@wit.id",
            "role": {
                "guid": "uuid-role-cashier",
                "name": "Cashier"
            },
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "detail": {
                "full_name": "Kasir Satu",
                "email": "kasir1@wit.id",
                "phone_number": "08123456789",
                "gender": "",
                "address": null,
                "city": null,
                "province": null
            },
            "url_image": "",
            "last_login": "2026-06-22T08:00:00.000000Z",
            "created_at": "2026-06-20T10:00:00.000000Z",
            "updated_at": "2026-06-22T08:00:00.000000Z"
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
        "message_en": "User not found.",
        "message_id": "User tidak ditemukan."
    }
}
```

---

## 4. Update User — `PUT /users/update`

### Request Body

```json
{
    "guid": "uuid-user",
    "username": "kasir1@wit.id",
    "role_guid": "uuid-role-cashier",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
    "full_name": "Kasir Satu Updated",
    "email": "kasir1@wit.id",
    "phone_number": "08123456789",
    "gender": "Laki-laki",
    "address": "Jl. Baru No. 5",
    "city": "Jakarta",
    "province": "DKI Jakarta",
    "is_active": true,
    "password": "passwordbaru123",
    "confirm_password": "passwordbaru123"
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `username` | required, email, max:255, unique (ignore self) |
| `role_guid` | required, string, exists |
| `guid_cabang` | nullable, string, exists |
| `full_name` | required, string, max:255 |
| `email` | nullable, email, max:255 |
| `phone_number` | nullable, string, max:50 |
| `gender` | nullable, string, max:30 |
| `address` | nullable, string |
| `city` | nullable, string, max:255 |
| `province` | nullable, string, max:255 |
| `is_active` | nullable, boolean |
| `password` | nullable, string, min:6 (kosongkan jika tidak diubah) |
| `confirm_password` | nullable, same:password (required jika password diisi) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-user",
            "username": "kasir1@wit.id",
            "role": {
                "guid": "uuid-role-cashier",
                "name": "Cashier"
            },
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "detail": {
                "full_name": "Kasir Satu Updated",
                "email": "kasir1@wit.id",
                "phone_number": "08123456789",
                "gender": "Laki-laki",
                "address": "Jl. Baru No. 5",
                "city": "Jakarta",
                "province": "DKI Jakarta"
            },
            "url_image": "",
            "last_login": "2026-06-22T08:00:00.000000Z",
            "created_at": "2026-06-20T10:00:00.000000Z",
            "updated_at": "2026-06-22T13:00:00.000000Z"
        },
        "message_en": "User updated.",
        "message_id": "User berhasil diperbarui."
    }
}
```

---

## 5. Delete User — `DELETE /users/{guid}`

Soft delete (set `is_active = false`). User tidak benar-benar dihapus dari database.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "User deactivated.",
        "message_id": "User berhasil dinonaktifkan."
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
        "message_en": "User not found.",
        "message_id": "User tidak ditemukan."
    }
}
```

---

## Data Structures

### User Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `username` | string (email) | Username untuk login |
| `role` | object or null | `{ guid, name }` |
| `guid_cabang` | string (UUID) | GUID cabang |
| `is_active` | boolean | Status aktif |
| `detail` | object or null | `{ full_name, email, phone_number, gender, address, city, province }` |
| `url_image` | string | URL foto profil |
| `last_login` | string (ISO 8601) or null | Terakhir login |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Password Hashing

```
password = base64_encode(sha256(raw_password + salt))
salt = base64_encode(random_bytes(16))
```
