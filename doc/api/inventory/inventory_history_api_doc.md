# Inventory History API Documentation

Base URL: `/inventory`

Semua endpoint inventory history menggunakan middleware `EnsureApiToken`.

Riwayat mutasi stok disimpan pada tabel `product.inventory_history`. Setiap perubahan `current_stock` di `product.inventories` WAJIB melalui service `InventoryService::adjustStock()` yang otomatis mencatat history.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/inventory/adjust` | EnsureApiToken | Adjust stok (in/out/adjustment) |
| `POST` | `/inventory/history` | EnsureApiToken | List riwayat mutasi inventory |

---

## 1. Adjust Stock - `POST /inventory/adjust`

Melakukan mutasi stok (in/out/adjustment) dan mencatat history.

### Request Body

```json
{
    "inventory_guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
    "type": "out",
    "qty": 10,
    "reference_type": "order",
    "reference_id": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
    "notes": "Deduct stock for order #ORD-001"
}
```

### Validation

| Field | Rule |
|---|---|
| `inventory_guid` | required, string, exists:product.inventories,guid |
| `type` | required, string, in:in,out,adjustment |
| `qty` | required, numeric, min:0.01 |
| `reference_type` | nullable, string, in:order,manual_adjustment |
| `reference_id` | nullable, string, uuid |
| `notes` | nullable, string, max:500 |

### Logic

- `current_stock` di-update real-time:
  - `type = in` → `current_stock + qty`
  - `type = out` → `current_stock - qty`
  - `type = adjustment` → `current_stock = qty` (absolute)
- Untuk `type = out`, validasi stok cukup (`current_stock >= qty`).
- `stock_before` dan `stock_after` dicatat otomatis.
- `created_by` diisi dari authenticated user.
- Idempotent untuk reference yang sama: sudah ada history dengan `reference_type` + `reference_id` yang sama = tolak (kecuali `manual_adjustment`).

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "ffffffff-1111-4111-8111-000000000001",
            "inventory": {
                "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
                "product_name": "Nasi Goreng Special"
            },
            "type": "out",
            "qty": 10,
            "stock_before": 50,
            "stock_after": 40,
            "reference_type": "order",
            "reference_id": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
            "notes": "Deduct stock for order #ORD-001",
            "created_at": "2026-06-17T10:00:00.000000Z"
        },
        "message_en": "Stock adjusted successfully.",
        "message_id": "Stok berhasil disesuaikan."
    }
}
```

### Error - Insufficient Stock (422)

```json
{
    "response": {
        "code": "05",
        "status": "failed",
        "data": {
            "product_name": "Nasi Goreng Special",
            "current_stock": 5,
            "required_stock": 10,
            "unit": "pcs"
        },
        "message_en": "Insufficient stock. Available: 5, requested: 10",
        "message_id": "Stok tidak mencukupi. Tersedia: 5, diminta: 10"
    }
}
```

### Error - Duplicate Adjustment (409)

```json
{
    "response": {
        "code": "06",
        "status": "failed",
        "data": {
            "history_guid": "ffffffff-1111-4111-8111-000000000001"
        },
        "message_en": "Stock adjustment for this reference already exists.",
        "message_id": "Penyesuaian stok untuk referensi ini sudah ada."
    }
}
```

---

## 2. List History - `POST /inventory/history`

Mendapatkan daftar riwayat mutasi stok untuk suatu inventory.

### Request Body (with filter)

```json
{
    "filter": {
        "set_inventory_guid": true,
        "inventory_guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
        "set_type": false,
        "type": "out",
        "set_reference_type": false,
        "reference_type": "order",
        "set_from_date": true,
        "from_date": "2026-06-01",
        "set_to_date": true,
        "to_date": "2026-06-17"
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
| `filter.set_inventory_guid` | required, boolean |
| `filter.inventory_guid` | required if set, string, exists:product.inventories,guid |
| `filter.set_type` | nullable, boolean |
| `filter.type` | nullable, string, in:in,out,adjustment |
| `filter.set_reference_type` | nullable, boolean |
| `filter.reference_type` | nullable, string, in:order,manual_adjustment |
| `filter.set_from_date` | nullable, boolean |
| `filter.from_date` | nullable, date |
| `filter.set_to_date` | nullable, boolean |
| `filter.to_date` | nullable, date |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:created_at,type,qty,stock_before,stock_after,reference_type |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "ffffffff-1111-4111-8111-000000000001",
                "inventory_guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
                "product_name": "Nasi Goreng Special",
                "type": "out",
                "qty": 10,
                "stock_before": 50,
                "stock_after": 40,
                "reference_type": "order",
                "reference_id": "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
                "notes": "Deduct stock for order #ORD-001",
                "created_by": {
                    "guid": "uuid-user",
                    "username": "achmad"
                },
                "created_at": "2026-06-17T10:00:00.000000Z",
                "updated_at": "2026-06-17T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "total": 25,
            "per_page": 20,
            "current_page": 1,
            "last_page": 2
        }
    }
}
```

---

## Data Structures

### InventoryHistory Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier history |
| `inventory_guid` | string (UUID) | GUID inventory terkait |
| `product_name` | string | Nama produk (denormalisasi/dari relasi) |
| `type` | string | `in` / `out` / `adjustment` |
| `qty` | float | Jumlah mutasi (selalu positif) |
| `stock_before` | float | Stok sebelum mutasi |
| `stock_after` | float | Stok setelah mutasi |
| `reference_type` | string or null | `order` / `manual_adjustment` |
| `reference_id` | string (UUID) or null | GUID referensi |
| `notes` | string or null | Keterangan tambahan |
| `created_by` | object or null | `{ guid, username }` |
| `created_at` | string (ISO 8601) | Waktu mutasi terjadi |
| `updated_at` | string (ISO 8601) | Waktu terakhir update |

## Database Notes

Table: `product.inventory_history`

Important constraints:
- `guid` unique.
- `inventory_id` references `product.inventories.guid`.
- `created_by` references `authentication.users.guid`.
- Indexes on: `inventory_id`, `product_guid`, `id_cabang`, `reference_type`, `reference_id`, `created_at`.
