# Authentication API Documentation

Base URL: `/authentication`

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/authentication/login` | EnsureApiToken | Login user |
| `POST` | `/authentication/user/register` | EnsureApiToken | Registrasi user baru |

---

## 1. Login — `POST /authentication/login`

Login user menggunakan username (email) dan password.

### Header

```
token: <access_token>
```

### Request Body

```json
{
    "username": "xander@gmail.com",
    "password": "gmail.com"
}
```

### Validation

| Field | Rule |
|---|---|
| `username` | required, email, max:255 |
| `password` | required, string |

### Logic
1. Validasi token dari header
2. Cari user berdasarkan username (email)
3. Verifikasi password dengan salt (SHA-256)
4. Buat session baru
5. Update `last_login` user dan `last_used_at` token

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "authentication_guid": "a0000000-0000-4000-8000-000000000001",
            "users_guid": "b0000000-0000-4000-8000-000000000001",
            "username": "xander@gmail.com",
            "role": {
                "guid": "c0000000-0000-4000-8000-000000000001",
                "name": "Superadmin"
            },
            "is_active": true,
            "url_image": "https://storage.googleapis.com/gavra-invest-storage-production/b55ccd10-0ab2-4462-ba78-4d47367fe3f3.jpg",
            "fcm_token": "token",
            "last_login": "2026-06-04T12:00:00.000000Z",
            "detail_data": {
                "phone_number": "02212",
                "email": "xander@gmail.com",
                "full_name": "Xander",
                "gender": "Laki-laki",
                "address": "WIT",
                "additional_address": null,
                "city": "",
                "province": "",
                "date_of_birth": "2000-01-01"
            },
            "used_trial": true,
            "is_verified": true
        }
    }
}
```

### Error — Invalid Credentials (401)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Invalid username or password.",
        "message_id": "Username atau password tidak valid."
    }
}
```

### Error — Invalid Token (401)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Invalid or expired token.",
        "message_id": "Token tidak valid atau sudah kedaluwarsa."
    }
}
```

---

## 2. Register — `POST /authentication/user/register`

Registrasi user baru (role default: `Users`).

### Header

```
token: <access_token>
```

### Request Body

```json
{
    "fullname": "Budi Santoso",
    "email": "budi@example.com",
    "gender": "Laki-laki",
    "birth_date": "1995-06-15",
    "phone_number": "081234567899",
    "password": "password123",
    "confirm_password": "password123",
    "additional_address": null,
    "city": "Jakarta",
    "province": "DKI Jakarta"
}
```

### Validation

| Field | Rule |
|---|---|
| `fullname` | required, string, max:255 |
| `email` | required, email, max:255, unique |
| `gender` | required, in:Laki-laki,Perempuan,Tidak-Spesifik |
| `birth_date` | nullable, date_format:Y-m-d |
| `phone_number` | nullable, string, max:50 |
| `password` | required, string, min:6 |
| `confirm_password` | required, same:password |
| `additional_address` | nullable |
| `city` | nullable, string, max:255 |
| `province` | nullable, string, max:255 |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "authentication_guid": "a0000000-0000-4000-8000-000000000002",
            "users_guid": "b0000000-0000-4000-8000-000000000002",
            "password": "base64_encoded_hash",
            "salt": "base64_encoded_salt",
            "username": "budi@example.com",
            "role": {
                "guid": "c0000000-0000-4000-8000-000000000005",
                "name": "Users"
            },
            "is_active": true,
            "url_image": "",
            "fcm_token": "",
            "last_login": "0001-01-01T00:00:00Z",
            "detail_data": {
                "phone_number": "081234567899",
                "email": "budi@example.com",
                "full_name": "Budi Santoso",
                "gender": "Laki-laki",
                "address": null,
                "additional_address": null,
                "city": "Jakarta",
                "province": "DKI Jakarta",
                "date_of_birth": "1995-06-15"
            },
            "used_trial": true,
            "is_verified": true
        }
    }
}
```

### Error — Email Already Registered (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Email already registered.",
        "message_id": "Email sudah terdaftar."
    }
}
```

---

## Data Structures

### User Data Object

| Field | Type | Description |
|---|---|---|
| `authentication_guid` | string (UUID) | Session GUID |
| `users_guid` | string (UUID) | User GUID |
| `username` | string | Email user |
| `role` | object | `{ guid, name }` |
| `is_active` | boolean | Status aktif user |
| `url_image` | string | URL foto profil |
| `fcm_token` | string | Firebase Cloud Messaging token |
| `last_login` | string (ISO 8601) | Waktu login terakhir |
| `detail_data` | object | Data detail user |
| `used_trial` | boolean | Status trial |
| `is_verified` | boolean | Status verifikasi |

### Detail Data Object

| Field | Type | Description |
|---|---|---|
| `phone_number` | string | Nomor telepon |
| `email` | string | Email |
| `full_name` | string | Nama lengkap |
| `gender` | string | Laki-laki / Perempuan / Tidak-Spesifik |
| `address` | string or null | Alamat |
| `additional_address` | string or null | Alamat tambahan |
| `city` | string or null | Kota |
| `province` | string or null | Provinsi |
| `date_of_birth` | string or null | Tanggal lahir (Y-m-d) |

### Role List (Fixed)

| Role | GUID |
|---|---|
| **Superadmin** | `c0000000-0000-4000-8000-000000000001` |
| **Owner** | `c0000000-0000-4000-8000-000000000002` |
| **Manager** | `c0000000-0000-4000-8000-000000000003` |
| **Cashier** | `c0000000-0000-4000-8000-000000000004` |
| **Users** | `c0000000-0000-4000-8000-000000000005` |
