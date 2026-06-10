# Shift API Documentation

Base URL: `/shift`

Semua endpoint shift menggunakan middleware `EnsureApiToken`.

Data shift tersimpan di table `orders.shifts`. Relasi order memakai `orders.orders.shift_id` ke `orders.shifts.id`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/shift/store` | EnsureApiToken | Buka shift baru |
| `PUT` | `/shift/close` | EnsureApiToken | Tutup shift |
| `GET` | `/shift/active` | EnsureApiToken | Cek shift aktif user ini |
| `GET` | `/shift/{guid}` | EnsureApiToken | Detail shift + summary + orders |
| `POST` | `/shift` | EnsureApiToken | List shift (dengan filter) |

---

## 1. Open Shift — `POST /shift/store`

Membuka shift baru untuk cashier yang terautentikasi.

### Request Body

```json
{
    "opened_at": "2026-06-04T08:00:00+07:00",
    "work_hours": 8,
    "opening_balance": 500000,
    "notes": "Shift pagi"
}
```

### Validation

| Field | Rule |
|---|---|
| `opened_at` | required, date (ISO 8601) |
| `work_hours` | required, numeric, min:0.25, max:24 |
| `opening_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

### Logic
1. Ambil user dari API token
2. Tolak jika user sudah punya shift `open`
3. Generate `shift_number`: `SH-{Ymd}-{NNN}`
4. `expected_balance = opening_balance`
5. Status `open`

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "550e8400-e29b-41d4-a716-446655440000",
            "shift_number": "SH-20260604-001",
            "user": {
                "guid": "660e8400-e29b-41d4-a716-446655440001",
                "full_name": "Ahmad Kasir",
                "username": "ahmad",
                "role": "Cashier"
            },
            "opened_at": "2026-06-04T08:00:00+07:00",
            "closed_at": null,
            "work_hours": 8,
            "opening_balance": 500000,
            "closing_balance": null,
            "expected_balance": 500000,
            "difference": null,
            "status": "open",
            "notes": "Shift pagi",
            "summary": {
                "total_sales": 0,
                "cash_sales": 0,
                "digital_sales": 0,
                "order_count": 0,
                "paid_order_count": 0,
                "pending_payment_count": 0
            },
            "orders": [],
            "created_at": "2026-06-04T08:00:00.000000Z",
            "updated_at": "2026-06-04T08:00:00.000000Z"
        },
        "message_en": "Shift opened successfully.",
        "message_id": "Shift berhasil dibuka."
    }
}
```

### Error — Already Active Shift (409)

```json
{
    "response": {
        "code": "03",
        "status": "failed",
        "data": null,
        "message_en": "You already have an active shift.",
        "message_id": "Anda masih memiliki shift aktif."
    }
}
```

---

## 2. Close Shift — `PUT /shift/close`

Menutup shift yang sedang aktif.

### Request Body

```json
{
    "guid": "550e8400-e29b-41d4-a716-446655440000",
    "closed_at": "2026-06-04T16:00:00+07:00",
    "work_hours": 8,
    "closing_balance": 2500000,
    "notes": "Shift selesai lancar"
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists:orders.shifts |
| `closed_at` | required, date (ISO 8601) |
| `work_hours` | required, numeric, min:0.25, max:24 |
| `closing_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

### Logic
1. Cari shift berdasarkan `guid`, milik user login, status `open`
2. Hitung summary sales dari order yang punya `shift_id`
3. `expected_balance = opening_balance + cash_sales`
4. `difference = closing_balance - expected_balance`
5. Status `closed`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "550e8400-e29b-41d4-a716-446655440000",
            "shift_number": "SH-20260604-001",
            "user": {
                "guid": "660e8400-e29b-41d4-a716-446655440001",
                "full_name": "Ahmad Kasir",
                "username": "ahmad",
                "role": "Cashier"
            },
            "opened_at": "2026-06-04T08:00:00+07:00",
            "closed_at": "2026-06-04T16:00:00+07:00",
            "work_hours": 8,
            "opening_balance": 500000,
            "closing_balance": 2500000,
            "expected_balance": 2400000,
            "difference": 100000,
            "status": "closed",
            "notes": "Shift selesai lancar",
            "summary": {
                "total_sales": 2100000,
                "cash_sales": 1900000,
                "digital_sales": 200000,
                "order_count": 24,
                "paid_order_count": 23,
                "pending_payment_count": 1
            },
            "orders": [
                {
                    "guid": "770e8400-e29b-41d4-a716-446655440002",
                    "order_number": "ORD-20260604-001",
                    "customer_name": "Budi",
                    "status": "completed",
                    "payment_status": "paid",
                    "total_amount": 150000,
                    "paid_amount": 150000,
                    "ordered_at": "2026-06-04T09:15:00+07:00"
                }
            ],
            "created_at": "2026-06-04T08:00:00.000000Z",
            "updated_at": "2026-06-04T16:00:00.000000Z"
        },
        "message_en": "Shift closed successfully.",
        "message_id": "Shift berhasil ditutup."
    }
}
```

**Catatan `difference`**: nilai positif = lebih (uang fisik lebih banyak), negatif = kurang.

### Error — Shift Not Open (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Shift is not open or does not belong to this user.",
        "message_id": "Shift tidak aktif atau bukan milik user ini."
    }
}
```

---

## 3. Active Shift — `GET /shift/active`

Mengecek apakah user ini memiliki shift yang sedang aktif.

### Response (200) — Has Active Shift

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "550e8400-e29b-41d4-a716-446655440000",
            "shift_number": "SH-20260604-001",
            "user": {
                "guid": "660e8400-e29b-41d4-a716-446655440001",
                "full_name": "Ahmad Kasir",
                "username": "ahmad",
                "role": "Cashier"
            },
            "opened_at": "2026-06-04T08:00:00+07:00",
            "closed_at": null,
            "work_hours": 8,
            "opening_balance": 500000,
            "closing_balance": null,
            "expected_balance": 2400000,
            "difference": null,
            "status": "open",
            "notes": "Shift pagi",
            "summary": {
                "total_sales": 2100000,
                "cash_sales": 1900000,
                "digital_sales": 200000,
                "order_count": 24,
                "paid_order_count": 23,
                "pending_payment_count": 1
            },
            "orders": [],
            "created_at": "2026-06-04T08:00:00.000000Z",
            "updated_at": "2026-06-04T10:30:00.000000Z"
        }
    }
}
```

### Response (200) — No Active Shift

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null
    }
}
```

---

## 4. Detail Shift — `GET /shift/{guid}`

Menampilkan detail shift lengkap dengan summary sales dan daftar order (max 50 terbaru).

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "550e8400-e29b-41d4-a716-446655440000",
            "shift_number": "SH-20260604-001",
            "user": {
                "guid": "660e8400-e29b-41d4-a716-446655440001",
                "full_name": "Ahmad Kasir",
                "username": "ahmad",
                "role": "Cashier"
            },
            "opened_at": "2026-06-04T08:00:00+07:00",
            "closed_at": "2026-06-04T16:00:00+07:00",
            "work_hours": 8,
            "opening_balance": 500000,
            "closing_balance": 2500000,
            "expected_balance": 2400000,
            "difference": 100000,
            "status": "closed",
            "notes": "Shift selesai lancar",
            "summary": {
                "total_sales": 2100000,
                "cash_sales": 1900000,
                "digital_sales": 200000,
                "order_count": 24,
                "paid_order_count": 23,
                "pending_payment_count": 1
            },
            "orders": [
                {
                    "guid": "770e8400-e29b-41d4-a716-446655440002",
                    "order_number": "ORD-20260604-001",
                    "customer_name": "Budi",
                    "status": "completed",
                    "payment_status": "paid",
                    "total_amount": 150000,
                    "paid_amount": 150000,
                    "ordered_at": "2026-06-04T09:15:00+07:00"
                }
            ],
            "created_at": "2026-06-04T08:00:00.000000Z",
            "updated_at": "2026-06-04T16:00:00.000000Z"
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
        "message_en": "Shift not found.",
        "message_id": "Shift tidak ditemukan."
    }
}
```

---

## 5. List Shift — `POST /shift`

Mendapatkan daftar shift dengan filter dan pagination.

### Request Body

```json
{
    "filter": {
        "set_guid": false,
        "guid": "550e8400-e29b-41d4-a716-446655440000",
        "set_status": true,
        "status": "closed",
        "set_user_guid": true,
        "user_guid": "660e8400-e29b-41d4-a716-446655440001",
        "set_from_date": true,
        "from_date": "2026-06-01",
        "set_to_date": true,
        "to_date": "2026-06-04"
    },
    "limit": 20,
    "page": 1,
    "order": "opened_at",
    "sort": "DESC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_status` | nullable, boolean |
| `filter.status` | nullable, string, in:open,closed |
| `filter.set_user_guid` | nullable, boolean |
| `filter.user_guid` | nullable, string, exists:authentication.users |
| `filter.set_from_date` | nullable, boolean |
| `filter.from_date` | nullable, date |
| `filter.set_to_date` | nullable, boolean |
| `filter.to_date` | nullable, date |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:shift_number,opened_at,closed_at,created_at (default: opened_at) |
| `sort` | nullable, string, in:ASC,DESC (default: DESC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "data": [
                {
                    "guid": "550e8400-e29b-41d4-a716-446655440000",
                    "shift_number": "SH-20260604-001",
                    "user": {
                        "guid": "660e8400-e29b-41d4-a716-446655440001",
                        "full_name": "Ahmad Kasir",
                        "username": "ahmad",
                        "role": "Cashier"
                    },
                    "opened_at": "2026-06-04T08:00:00+07:00",
                    "closed_at": "2026-06-04T16:00:00+07:00",
                    "work_hours": 8,
                    "opening_balance": 500000,
                    "closing_balance": 2500000,
                    "expected_balance": 2400000,
                    "difference": 100000,
                    "status": "closed",
                    "notes": null,
                    "summary": {
                        "total_sales": 2100000,
                        "cash_sales": 1900000,
                        "digital_sales": 200000,
                        "order_count": 24,
                        "paid_order_count": 23,
                        "pending_payment_count": 1
                    },
                    "orders": [],
                    "created_at": "2026-06-04T08:00:00.000000Z",
                    "updated_at": "2026-06-04T16:00:00.000000Z"
                }
            ],
            "meta": {
                "current_page": 1,
                "last_page": 1,
                "per_page": 20,
                "total": 1
            }
        }
    }
}
```

---

## Data Structures

### Shift Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `shift_number` | string | Format: `SH-{Ymd}-{NNN}` |
| `user` | object | `{ guid, full_name, username, role }` |
| `opened_at` | string (ISO 8601) | Waktu buka shift |
| `closed_at` | string (ISO 8601) or null | Waktu tutup shift |
| `work_hours` | float | Durasi shift (dari frontend) |
| `opening_balance` | float | Saldo awal |
| `closing_balance` | float or null | Saldo akhir (saat tutup) |
| `expected_balance` | float | `opening_balance + cash_sales` |
| `difference` | float or null | `closing_balance - expected_balance` |
| `status` | string | `open` atau `closed` |
| `notes` | string or null | Catatan shift |
| `summary` | object | Lihat Summary Object |
| `orders` | array | Daftar order (max 50), hanya ada jika `withOrders: true` |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Summary Object

| Field | Type | Description |
|---|---|---|
| `total_sales` | float | SUM total_amount order status `completed` |
| `cash_sales` | float | SUM payment amount method `cash` status `paid` |
| `digital_sales` | float | SUM payment amount non-cash status `paid` |
| `order_count` | int | COUNT distinct orders di shift ini |
| `paid_order_count` | int | COUNT order dgn payment_status `paid` |
| `pending_payment_count` | int | COUNT order dgn payment_status `unpaid`/`partial` |

---

## Integrasi dengan Order

Saat create order via `POST /api/orders/store`, kirim `shift_guid` opsional:

```json
{
    "shift_guid": "550e8400-e29b-41d4-a716-446655440000",
    "items": [...],
    "payments": [...]
}
```

- Jika `shift_guid` dikirim, backend akan attach order ke shift tersebut
- Order response akan menyertakan data shift:

```json
{
    "shift": {
        "guid": "550e8400-e29b-41d4-a716-446655440000",
        "shift_number": "SH-20260604-001",
        "status": "open"
    }
}
```

---

## State Diagram

```
[No Shift] --(POST /shift/store)--> [Open]
    ^                                       |
    |                              (PUT /shift/close)
    |                                       |
    +------- [Closed] <---------------------+
```

- Satu user hanya boleh punya **satu shift open** dalam satu waktu
- Shift `closed` tidak bisa dibuka lagi
