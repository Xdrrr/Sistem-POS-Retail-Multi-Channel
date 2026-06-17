# Inventory API Documentation

Base URL: `/inventory`

Semua endpoint inventory menggunakan middleware `EnsureApiToken`.

Inventory disimpan pada tabel `product.inventories`. Modul ini dipakai untuk mengelola stok produk per cabang. Untuk fase awal POS restoran, seluruh stok memakai satuan `pcs` dan cabang default `PUSAT`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/inventory` | EnsureApiToken | List inventory |
| `POST` | `/inventory/store` | EnsureApiToken | Tambah inventory produk |
| `GET` | `/inventory/{guid}` | EnsureApiToken | Detail inventory |
| `PUT` | `/inventory/update` | EnsureApiToken | Update inventory |
| `DELETE` | `/inventory/{guid}` | EnsureApiToken | Nonaktifkan/hapus inventory |

---

## 1. List Inventory - `POST /inventory`

Mendapatkan daftar stok produk.

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "inventory-guid-here",
        "set_product_guid": false,
        "product_guid": "33333333-3333-4333-8333-000000000001",
        "set_id_cabang": true,
        "id_cabang": "PUSAT",
        "set_unit": false,
        "unit": "pcs",
        "set_is_active": true,
        "is_active": true,
        "set_low_stock": false,
        "low_stock": true
    },
    "limit": 20,
    "page": 1,
    "order": "product_name",
    "sort": "ASC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_product_guid` | nullable, boolean |
| `filter.product_guid` | nullable, string, exists:product.products,guid |
| `filter.set_id_cabang` | nullable, boolean |
| `filter.id_cabang` | nullable, string, max:50 |
| `filter.set_unit` | nullable, boolean |
| `filter.unit` | nullable, string, max:20 |
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `filter.set_low_stock` | nullable, boolean |
| `filter.low_stock` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:product_name,id_cabang,unit,current_stock,minimum_stock,is_active,created_at,updated_at |
| `sort` | nullable, string, in:ASC,DESC |

### Logic

- Jika `set_low_stock = true` dan `low_stock = true`, tampilkan stok dengan `current_stock <= minimum_stock`.
- Jika `set_id_cabang = false`, controller boleh memakai cabang default user login. Untuk fase awal gunakan `PUSAT`.
- Sorting wajib memakai whitelist kolom, jangan langsung memakai input `order` ke `orderBy`.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
                "product": {
                    "guid": "33333333-3333-4333-8333-000000000001",
                    "name": "Nasi Goreng Special",
                    "category": {
                        "guid": "11111111-1111-4111-8111-000000000001",
                        "name": "makanan"
                    },
                    "group": {
                        "guid": "22222222-2222-4222-8222-000000000002",
                        "name": "nasi"
                    }
                },
                "id_cabang": "PUSAT",
                "unit": "pcs",
                "current_stock": 100,
                "minimum_stock": 10,
                "is_low_stock": false,
                "is_active": true,
                "created_at": "2026-06-17T10:00:00.000000Z",
                "updated_at": "2026-06-17T10:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Inventory - `POST /inventory/store`

Membuat data stok untuk produk pada cabang tertentu.

### Request Body

```json
{
    "product_guid": "33333333-3333-4333-8333-000000000001",
    "id_cabang": "PUSAT",
    "unit": "pcs",
    "current_stock": 100,
    "minimum_stock": 10,
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `product_guid` | required, string, exists:product.products,guid |
| `id_cabang` | nullable, string, max:50 (default: PUSAT) |
| `unit` | nullable, string, max:20 (default: pcs) |
| `current_stock` | nullable, numeric, min:0 (default: 0) |
| `minimum_stock` | nullable, numeric, min:0 (default: 0) |
| `is_active` | nullable, boolean (default: true) |

### Logic

- Kombinasi `product_guid` dan `id_cabang` harus unik.
- Untuk fase awal, `id_cabang` default adalah `PUSAT`.
- Untuk fase awal POS restoran, `unit` default adalah `pcs`.

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
            "product": {
                "guid": "33333333-3333-4333-8333-000000000001",
                "name": "Nasi Goreng Special"
            },
            "id_cabang": "PUSAT",
            "unit": "pcs",
            "current_stock": 100,
            "minimum_stock": 10,
            "is_low_stock": false,
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T10:00:00.000000Z"
        },
        "message_en": "Inventory created successfully.",
        "message_id": "Inventory berhasil dibuat."
    }
}
```

### Error - Duplicate Inventory (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Inventory already exists for this product and branch.",
        "message_id": "Inventory untuk produk dan cabang ini sudah ada."
    }
}
```

---

## 3. Show Inventory - `GET /inventory/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
            "product": {
                "guid": "33333333-3333-4333-8333-000000000001",
                "name": "Nasi Goreng Special",
                "price": 28000,
                "category": {
                    "guid": "11111111-1111-4111-8111-000000000001",
                    "name": "makanan"
                },
                "group": {
                    "guid": "22222222-2222-4222-8222-000000000002",
                    "name": "nasi"
                }
            },
            "id_cabang": "PUSAT",
            "unit": "pcs",
            "current_stock": 100,
            "minimum_stock": 10,
            "is_low_stock": false,
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T10:00:00.000000Z"
        }
    }
}
```

### Error - Not Found (404)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Inventory not found.",
        "message_id": "Inventory tidak ditemukan."
    }
}
```

---

## 4. Update Inventory - `PUT /inventory/update`

Mengubah stok, minimum stok, status aktif, atau data cabang/satuan inventory.

### Request Body

```json
{
    "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
    "product_guid": "33333333-3333-4333-8333-000000000001",
    "id_cabang": "PUSAT",
    "unit": "pcs",
    "current_stock": 125,
    "minimum_stock": 15,
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists:product.inventories,guid |
| `product_guid` | required, string, exists:product.products,guid |
| `id_cabang` | nullable, string, max:50 |
| `unit` | nullable, string, max:20 |
| `current_stock` | required, numeric, min:0 |
| `minimum_stock` | nullable, numeric, min:0 |
| `is_active` | nullable, boolean |

### Logic

- Jika `product_guid` atau `id_cabang` diubah, pastikan kombinasi baru tidak bentrok dengan inventory lain.
- `current_stock` TIDAK BOLEH diubah langsung di endpoint ini. Gunakan endpoint `/inventory/adjust` untuk mutasi stok agar tercatat di `inventory_history`. Kolom ini tetap dikirim untuk update data non-stok (misal mengisi nilai awal inventory baru).
- Untuk keperluan mutasi stok (in/out/adjustment), gunakan `POST /inventory/adjust`.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "7c5b5a21-f805-45cb-8f76-9c41d741ca91",
            "product": {
                "guid": "33333333-3333-4333-8333-000000000001",
                "name": "Nasi Goreng Special"
            },
            "id_cabang": "PUSAT",
            "unit": "pcs",
            "current_stock": 125,
            "minimum_stock": 15,
            "is_low_stock": false,
            "is_active": true,
            "created_at": "2026-06-17T10:00:00.000000Z",
            "updated_at": "2026-06-17T10:30:00.000000Z"
        },
        "message_en": "Inventory updated successfully.",
        "message_id": "Inventory berhasil diperbarui."
    }
}
```

---

## 5. Delete Inventory - `DELETE /inventory/{guid}`

Menghapus atau menonaktifkan data inventory.

### Recommended Behavior

Untuk data inventory yang sudah pernah dipakai transaksi, lebih aman melakukan soft behavior:

- set `is_active = false`
- jangan hapus fisik data jika sudah ada histori stok atau order yang memakai produk tersebut

Jika belum ada histori dan bisnis mengizinkan, data boleh dihapus fisik.

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Inventory deleted successfully.",
        "message_id": "Inventory berhasil dihapus."
    }
}
```

### Error - Not Found (404)

```json
{
    "response": {
        "code": "01",
        "status": "failed",
        "data": null,
        "message_en": "Inventory not found.",
        "message_id": "Inventory tidak ditemukan."
    }
}
```

---

## Stock Deduction Rule

Stok inventory berkurang saat order berubah menjadi `completed`.

### Rule

- Jangan kurangi stok saat order dibuat.
- Jangan kurangi stok saat order masih `draft` atau `open`.
- Jangan kurangi stok saat payment dibuat.
- Jangan kurangi stok untuk order `cancelled`.
- Deduction harus idempotent: order yang sama tidak boleh mengurangi stok dua kali.

### Basic Calculation

Untuk fase awal, setiap `orders.order_items.quantity` mengurangi `product.inventories.current_stock` berdasarkan:

```text
inventory.product_guid = order_items.product_guid
inventory.id_cabang = user.id_cabang or PUSAT
deduct_qty = order_items.quantity
```

Karena satuan awal semua produk adalah `pcs`, belum ada konversi satuan.

### Insufficient Stock Recommendation

Jika stok tidak cukup saat order akan diselesaikan:

```json
{
    "response": {
        "code": "05",
        "status": "failed",
        "data": {
            "product": {
                "guid": "33333333-3333-4333-8333-000000000001",
                "name": "Nasi Goreng Special"
            },
            "current_stock": 1,
            "required_stock": 2,
            "unit": "pcs"
        },
        "message_en": "Insufficient stock.",
        "message_id": "Stok tidak mencukupi."
    }
}
```

---

## Data Structures

### Inventory Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier inventory |
| `product` | object | Produk yang memiliki stok |
| `id_cabang` | string | Kode cabang, default `PUSAT` |
| `unit` | string | Satuan stok, default `pcs` |
| `current_stock` | float | Stok tersedia saat ini |
| `minimum_stock` | float | Batas minimum stok |
| `is_low_stock` | boolean | `current_stock <= minimum_stock` |
| `is_active` | boolean | Status aktif inventory |
| `created_at` | string (ISO 8601) | Waktu dibuat |
| `updated_at` | string (ISO 8601) | Waktu terakhir update |

### Product Object inside Inventory

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier produk |
| `name` | string | Nama produk |
| `price` | float | Harga jual produk |
| `category` | object or null | `{ guid, name }` |
| `group` | object or null | `{ guid, name }` |

## Database Notes

Table: `product.inventories`

Important constraints:

- `guid` unique.
- `product_guid` references `product.products.guid`.
- Unique combination: `product_guid`, `id_cabang`.

Seeder behavior:

- `CatalogSeeder` membuat inventory untuk semua produk seed.
- Default `id_cabang`: `PUSAT`.
- Default `unit`: `pcs`.
- Default `current_stock`: `0`.
- Default `minimum_stock`: `0`.
