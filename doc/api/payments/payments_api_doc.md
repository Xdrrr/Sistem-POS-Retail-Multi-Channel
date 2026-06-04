# Payments API Documentation

Base URL: `/payments`

Semua endpoint payments menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/payments` | EnsureApiToken | List pembayaran |
| `POST` | `/payments/store` | EnsureApiToken | Tambah pembayaran baru |
| `GET` | `/payments/{guid}` | EnsureApiToken | Detail pembayaran |

---

## 1. List Payments — `POST /payments`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "payment-guid-here",
        "set_payment_number": false,
        "payment_number": "PAY-RPT-20260604-001",
        "set_method": true,
        "method": "cash",
        "set_status": true,
        "status": "paid"
    },
    "limit": 20,
    "page": 1,
    "order": "paid_at",
    "sort": "DESC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_payment_number` | nullable, boolean |
| `filter.payment_number` | nullable, string |
| `filter.set_method` | nullable, boolean |
| `filter.method` | nullable, string |
| `filter.set_status` | nullable, boolean |
| `filter.status` | nullable, string |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:payment_number,method,status,amount,paid_at,created_at,updated_at |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "e0000000-0000-4000-8000-000000000001",
                "payment_number": "PAY-RPT-20260604081200-0001",
                "order": {
                    "guid": "d0000000-0000-4000-8000-000000000001",
                    "order_number": "ORD-RPT-20260604-001",
                    "total_amount": 61600,
                    "payment_status": "paid"
                },
                "method": "cash",
                "status": "paid",
                "amount": 61600,
                "paid_at": "2026-06-04T08:12:00.000000Z",
                "reference_number": "CASH-RPT-00001",
                "notes": "Payment seed report.",
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            },
            {
                "guid": "e0000000-0000-4000-8000-000000000002",
                "payment_number": "PAY-RPT-20260604081200-0002",
                "order": {
                    "guid": "d0000000-0000-4000-8000-000000000002",
                    "order_number": "ORD-RPT-20260604-002",
                    "total_amount": 72000,
                    "payment_status": "paid"
                },
                "method": "qris",
                "status": "paid",
                "amount": 72000,
                "paid_at": "2026-06-04T08:19:00.000000Z",
                "reference_number": "QRIS-RPT-00002",
                "notes": "Payment seed report.",
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Payment — `POST /payments/store`

Menambahkan pembayaran ke order yang sudah ada.

### Request Body

```json
{
    "order_guid": "d0000000-0000-4000-8000-000000000001",
    "method": "qris",
    "status": "paid",
    "amount": 61600,
    "paid_at": "2026-06-04T10:15:00+07:00",
    "reference_number": "QRIS-20260604-001",
    "notes": "Pembayaran via QRIS"
}
```

### Validation

| Field | Rule |
|---|---|
| `order_guid` | required, string, exists:orders |
| `method` | required, string, in:cash,debit_card,credit_card,qris,transfer,e_wallet |
| `status` | nullable, string, in:pending,paid,failed,refunded (default: paid) |
| `amount` | required, numeric, min:0.01 |
| `paid_at` | nullable, date (default: now) |
| `reference_number` | nullable, string, max:100 |
| `notes` | nullable, string |

### Logic
1. Cari order berdasarkan `order_guid`
2. Buat payment record
3. Update `payment_status` order otomatis:
   - Jika total paid = 0 → `unpaid`
   - Jika total paid < total amount → `partial`
   - Jika total paid >= total amount → `paid`

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "e0000000-0000-4000-8000-000000000003",
            "payment_number": "PAY-20260604101500-ABCD",
            "order": {
                "guid": "d0000000-0000-4000-8000-000000000001",
                "order_number": "ORD-RPT-20260604-001",
                "total_amount": 61600,
                "payment_status": "paid"
            },
            "method": "qris",
            "status": "paid",
            "amount": 61600,
            "paid_at": "2026-06-04T10:15:00.000000Z",
            "reference_number": "QRIS-20260604-001",
            "notes": "Pembayaran via QRIS"
        },
        "message_en": "Payment created successfully.",
        "message_id": "Pembayaran berhasil dibuat."
    }
}
```

---

## 3. Show Payment — `GET /payments/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "e0000000-0000-4000-8000-000000000001",
            "payment_number": "PAY-RPT-20260604081200-0001",
            "order": {
                "guid": "d0000000-0000-4000-8000-000000000001",
                "order_number": "ORD-RPT-20260604-001",
                "total_amount": 61600,
                "payment_status": "paid"
            },
            "method": "cash",
            "status": "paid",
            "amount": 61600,
            "paid_at": "2026-06-04T08:12:00.000000Z",
            "reference_number": "CASH-RPT-00001",
            "notes": "Payment seed report.",
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
        "message_en": "Payment not found.",
        "message_id": "Pembayaran tidak ditemukan."
    }
}
```

---

## Data Structures

### Payment Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `payment_number` | string | Format: `PAY-{YmdHis}-{random4}` |
| `order` | object | `{ guid, order_number, total_amount, payment_status }` |
| `method` | string | `cash`, `debit_card`, `credit_card`, `qris`, `transfer`, `e_wallet` |
| `status` | string | `pending`, `paid`, `failed`, `refunded` |
| `amount` | float | Jumlah pembayaran |
| `paid_at` | string (ISO 8601) | Waktu bayar |
| `reference_number` | string or null | No. referensi (dari bank/QRIS) |
| `notes` | string or null | Catatan |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Payment Methods

| Method | Keterangan |
|---|---|
| `cash` | Tunai |
| `debit_card` | Kartu debit |
| `credit_card` | Kartu kredit |
| `qris` | QRIS |
| `transfer` | Transfer bank |
| `e_wallet` | Dompet digital (GoPay, OVO, DANA, dll) |

### Payment Statuses

| Status | Keterangan |
|---|---|
| `pending` | Menunggu konfirmasi |
| `paid` | Berhasil dibayar |
| `failed` | Gagal |
| `refunded` | Dikembalikan |
