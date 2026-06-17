# Reservations API Documentation

Base URL: `/reservations`

Semua endpoint reservations menggunakan middleware `EnsureApiToken`.

Reservasi meja disimpan pada tabel `orders.table_reservations`. Modul ini dipakai untuk mengelola booking meja oleh pelanggan.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/reservations` | EnsureApiToken | List reservasi |
| `POST` | `/reservations/store` | EnsureApiToken | Tambah reservasi baru |
| `GET` | `/reservations/{guid}` | EnsureApiToken | Detail reservasi |
| `PUT` | `/reservations/update` | EnsureApiToken | Update reservasi |
| `DELETE` | `/reservations/{guid}` | EnsureApiToken | Batalkan reservasi (soft delete) |

---

## 1. List Reservations — `POST /reservations`

Mendapatkan daftar reservasi dengan filter.

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "uuid-reservation",
        "set_table_number": true,
        "table_number": "A1",
        "set_status": false,
        "status": "confirmed",
        "set_reservation_date": true,
        "reservation_date": "2026-06-17",
        "set_guid_cabang": false,
        "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
        "set_is_active": true,
        "is_active": true
    },
    "limit": 20,
    "page": 1,
    "order": "reservation_date",
    "sort": "ASC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_table_number` | nullable, boolean |
| `filter.table_number` | nullable, string, max:30 |
| `filter.set_status` | nullable, boolean |
| `filter.status` | nullable, string, in:pending,confirmed,seated,completed,cancelled |
| `filter.set_reservation_date` | nullable, boolean |
| `filter.reservation_date` | nullable, date |
| `filter.set_guid_cabang` | nullable, boolean |
| `filter.guid_cabang` | nullable, string |
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:table_number,customer_name,reservation_date,reservation_time,status,guest_count,created_at |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "uuid-reservation",
                "table_number": "A1",
                "customer_name": "Budi Santoso",
                "customer_phone": "081234567890",
                "guest_count": 4,
                "reservation_date": "2026-06-17",
                "reservation_time": "12:00",
                "notes": null,
                "status": "confirmed",
                "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
                "is_active": true,
                "created_at": "2026-06-17T10:00:00.000000Z",
                "updated_at": "2026-06-17T10:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Reservation — `POST /reservations/store`

### Request Body

```json
{
    "table_guid": "uuid-table",
    "table_number": "A1",
    "customer_name": "Budi Santoso",
    "customer_phone": "081234567890",
    "guest_count": 4,
    "reservation_date": "2026-06-17",
    "reservation_time": "12:00",
    "notes": "Minta meja dekat jendela",
    "status": "pending",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001"
}
```

### Validation

| Field | Rule |
|---|---|
| `table_guid` | nullable, string, exists:orders.tables,guid |
| `table_number` | required, string, max:30 |
| `customer_name` | required, string, max:150 |
| `customer_phone` | nullable, string, max:30 |
| `guest_count` | nullable, integer, min:1 (default: 1) |
| `reservation_date` | required, date |
| `reservation_time` | required, string |
| `notes` | nullable, string, max:500 |
| `status` | nullable, string, in:pending,confirmed,seated,completed,cancelled (default: pending) |
| `guid_cabang` | nullable, string (default: PUSAT) |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-reservation",
            "table_number": "A1",
            "customer_name": "Budi Santoso",
            "customer_phone": "081234567890",
            "guest_count": 4,
            "reservation_date": "2026-06-17",
            "reservation_time": "12:00",
            "notes": "Minta meja dekat jendela",
            "status": "pending",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T10:00:00.000000Z"
        },
        "message_en": "Reservation created.",
        "message_id": "Reservasi berhasil dibuat."
    }
}
```

---

## 3. Show Reservation — `GET /reservations/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-reservation",
            "table_number": "A1",
            "customer_name": "Budi Santoso",
            "customer_phone": "081234567890",
            "guest_count": 4,
            "reservation_date": "2026-06-17",
            "reservation_time": "12:00",
            "notes": "Minta meja dekat jendela",
            "status": "confirmed",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T12:30:00.000000Z"
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
        "message_en": "Reservation not found.",
        "message_id": "Reservasi tidak ditemukan."
    }
}
```

---

## 4. Update Reservation — `PUT /reservations/update`

### Request Body

```json
{
    "guid": "uuid-reservation",
    "table_number": "A2",
    "customer_name": "Budi Santoso",
    "customer_phone": "081234567890",
    "guest_count": 5,
    "reservation_date": "2026-06-17",
    "reservation_time": "13:00",
    "notes": "Pindah meja",
    "status": "confirmed",
    "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001"
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists:orders.table_reservations,guid |
| `table_number` | nullable, string, max:30 |
| `customer_name` | nullable, string, max:150 |
| `customer_phone` | nullable, string, max:30 |
| `guest_count` | nullable, integer, min:1 |
| `reservation_date` | nullable, date |
| `reservation_time` | nullable, string |
| `notes` | nullable, string, max:500 |
| `status` | nullable, string, in:pending,confirmed,seated,completed,cancelled |
| `guid_cabang` | nullable, string |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-reservation",
            "table_number": "A2",
            "customer_name": "Budi Santoso",
            "customer_phone": "081234567890",
            "guest_count": 5,
            "reservation_date": "2026-06-17",
            "reservation_time": "13:00",
            "notes": "Pindah meja",
            "status": "confirmed",
            "guid_cabang": "aaaaaaaa-aaaa-4000-8000-000000000001",
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T14:00:00.000000Z"
        },
        "message_en": "Reservation updated.",
        "message_id": "Reservasi berhasil diperbarui."
    }
}
```

---

## 5. Delete (Cancel) Reservation — `DELETE /reservations/{guid}`

Menonaktifkan reservasi (soft delete: `is_active = false`).

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Reservation cancelled.",
        "message_id": "Reservasi dibatalkan."
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
        "message_en": "Reservation not found.",
        "message_id": "Reservasi tidak ditemukan."
    }
}
```

---

## Data Structures

### Reservation Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier reservasi |
| `table_guid` | string (UUID) or null | GUID meja (FK → orders.tables.guid) |
| `table_number` | string | Nomor meja |
| `customer_name` | string | Nama pelanggan |
| `customer_phone` | string or null | No. telepon |
| `guest_count` | integer | Jumlah tamu |
| `reservation_date` | string (date) | Tanggal reservasi (YYYY-MM-DD) |
| `reservation_time` | string | Jam reservasi (HH:MM) |
| `notes` | string or null | Catatan tambahan |
| `status` | string | `pending` / `confirmed` / `seated` / `completed` / `cancelled` |
| `guid_cabang` | string (UUID) | GUID cabang |
| `is_active` | boolean | Status aktif |
| `created_at` | string (ISO 8601) | Waktu dibuat |
| `updated_at` | string (ISO 8601) | Waktu terakhir update |

### Status Flow

```
pending → confirmed → seated → completed
                     ↘ cancelled
```

- `pending`: Menunggu konfirmasi
- `confirmed`: Sudah dikonfirmasi
- `seated`: Sudah duduk/mulai makan (link ke order)
- `completed`: Selesai
- `cancelled`: Dibatalkan

## Database Notes

Table: `orders.table_reservations`

Constraints:
- `guid` unique.
- `guid_cabang` references `authentication.cabang.guid`.
- Indexes on: `table_number`, `reservation_date`, `status`, `guid_cabang`.
