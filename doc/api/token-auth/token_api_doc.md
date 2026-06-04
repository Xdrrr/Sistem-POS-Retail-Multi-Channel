# Token Authentication API Documentation

Base URL: `/token`

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/token/auth` | - | Mendapatkan access token & refresh token |
| `POST` | `/token/refresh` | - | Refresh access token menggunakan refresh token |

---

## 1. Auth — `POST /token/auth`

Mendapatkan access token dan refresh token untuk mengakses API.

### Request Body

```json
{
    "app_name": "wit-dev",
    "app_key": "w1t-d3V",
    "device_id": "DEV-001",
    "device_type": "android",
    "fcm_token": "fcm-device-token-abc123",
    "ip_address": "192.168.1.100"
}
```

### Validation

| Field | Rule |
|---|---|
| `app_name` | required, string, max:100 |
| `app_key` | required, string, max:255 |
| `device_id` | required, string, max:255 |
| `device_type` | required, string, max:100 |
| `fcm_token` | nullable, string |
| `ip_address` | required, ip |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "name": "wit-dev",
            "device_id": "DEV-001",
            "device_type": "android",
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBfbmFtZSI6IndpdC1kZXYiLCJkZXZpY2VfaWQiOiJERVYtMDAxIiwiZGV2aWNlX3R5cGUiOiJhbmRyb2lkIiwiaXBfYWRkcmVzcyI6IjE5Mi4xNjguMS4xMDAiLCJleHAiOjE3NTQzMjY0MDB9.signature",
            "token_expired": "2026-06-05T12:00:00.000000Z",
            "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBfbmFtZSI6IndpdC1kZXYiLCJkZXZpY2VfaWQiOiJERVYtMDAxIiwiZGV2aWNlX3R5cGUiOiJhbmRyb2lkIiwiaXBfYWRkcmVzcyI6IjE5Mi4xNjguMS4xMDAiLCJleHAiOjE3OTU4NjI0MDB9.signature",
            "refresh_token_expired": "2027-06-04T12:00:00.000000Z",
            "is_login": false,
            "user_login": ""
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
        "message_en": "Invalid app credentials.",
        "message_id": "Kredensial aplikasi tidak valid."
    }
}
```

---

## 2. Refresh Token — `POST /token/refresh`

Memperbarui access token yang sudah mendekati kedaluwarsa.

### Header

| Header | Value |
|---|---|
| `refresh-token` | `<refresh_token_dari_auth>` |

### Logic
1. Cari token berdasarkan hash refresh token
2. Validasi masih dalam masa berlaku
3. Generate access token baru & refresh token baru
4. Update hash di database

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "name": "wit-dev",
            "device_id": "DEV-001",
            "device_type": "android",
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.<new_payload>.<new_signature>",
            "token_expired": "2026-06-05T12:05:00.000000Z",
            "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.<new_payload>.<new_signature>",
            "refresh_token_expired": "2027-06-04T12:05:00.000000Z",
            "is_login": false,
            "user_login": ""
        }
    }
}
```

### Error — Invalid/Expired (401)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Invalid or expired refresh token.",
        "message_id": "Refresh token tidak valid atau sudah kedaluwarsa."
    }
}
```

---

## Alur Penggunaan Token

```
Client                          Server
  |                                |
  |-- POST /token/auth ----------->|  (app_name + app_key)
  |<-- { token, refresh_token } ---|
  |                                |
  |-- POST /authentication/login ->|  (Header: token)
  |<-- { session_guid, user } -----|
  |                                |
  |-- POST /orders/store --------->|  (Header: token)
  |<-- { order data } -------------|
  |                                |
  |-- POST /token/refresh -------->|  (Header: refresh-token)
  |<-- { new token } --------------|
```

### Cara Kirim Token

Semua request ke endpoint yang menggunakan middleware `EnsureApiToken` wajib menyertakan token di header:

```
token: <access_token>
```

Atau:

```
Authorization: Bearer <access_token>
```

### Masa Berlaku

| Token | Masa Berlaku |
|---|---|
| Access Token | 1 hari |
| Refresh Token | 1 tahun |
