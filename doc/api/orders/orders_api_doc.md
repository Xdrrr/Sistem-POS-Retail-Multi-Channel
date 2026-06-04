# Orders API Documentation

Base URL: `/orders`

Semua endpoint orders menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/orders` | EnsureApiToken | List order |
| `POST` | `/orders/store` | EnsureApiToken | Buat order baru |
| `GET` | `/orders/{guid}` | EnsureApiToken | Detail order |
| `PUT` | `/orders/update` | EnsureApiToken | Update order |
| `DELETE` | `/orders/{guid}` | EnsureApiToken | Hapus order |

---

## 1. List Orders — `POST /orders`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "order-guid-here",
        "set_order_number": false,
        "order_number": "ORD-20260604-001",
        "set_status": true,
        "status": "completed",
        "set_payment_status": true,
        "payment_status": "paid",
        "set_order_type": false,
        "order_type": "dine_in"
    },
    "limit": 20,
    "page": 1,
    "order": "ordered_at",
    "sort": "DESC"
}
```

### Validation

| Field | Rule |
|---|---|
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_order_number` | nullable, boolean |
| `filter.order_number` | nullable, string |
| `filter.set_status` | nullable, boolean |
| `filter.status` | nullable, string |
| `filter.set_payment_status` | nullable, boolean |
| `filter.payment_status` | nullable, string |
| `filter.set_order_type` | nullable, boolean |
| `filter.order_type` | nullable, string |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:order_number,customer_name,order_type,status,payment_status,total_amount,ordered_at,created_at,updated_at |
| `sort` | nullable, string, in:ASC,DESC |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "d0000000-0000-4000-8000-000000000001",
                "order_number": "ORD-RPT-20260604-001",
                "shift": {
                    "guid": "shift-guid",
                    "shift_number": "SH-SEED-20260604-001",
                    "status": "open"
                },
                "cashier": {
                    "guid": "b0000000-0000-4000-8000-000000000002",
                    "full_name": "Dewi Lestari"
                },
                "customer_name": "Budi Santoso",
                "customer_phone": "081234567890",
                "table_number": "A1",
                "order_type": "dine_in",
                "status": "completed",
                "payment_status": "paid",
                "subtotal": 56000,
                "discount_amount": 0,
                "tax_amount": 5600,
                "total_amount": 61600,
                "notes": "Data seed report.",
                "ordered_at": "2026-06-04T08:07:00.000000Z",
                "items": [
                    {
                        "guid": "item-guid-1",
                        "product": {
                            "guid": "33333333-3333-4333-8333-000000000001",
                            "name": "Nasi Goreng Special"
                        },
                        "quantity": 2,
                        "unit_price": 28000,
                        "discount_amount": 0,
                        "subtotal": 56000,
                        "notes": null
                    }
                ],
                "payments": [
                    {
                        "guid": "payment-guid-1",
                        "payment_number": "PAY-RPT-20260604081200-0001",
                        "method": "cash",
                        "status": "paid",
                        "amount": 61600,
                        "paid_at": "2026-06-04T08:12:00.000000Z",
                        "reference_number": "CASH-RPT-00001",
                        "notes": "Payment seed report."
                    }
                ],
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Order — `POST /orders/store`

Membuat order baru beserta items dan payments.

### Request Body

```json
{
    "shift_guid": "55b7650b-1385-4785-afb6-4558e3fa09eb",
    "customer_name": "Budi Santoso",
    "customer_phone": "081234567890",
    "table_number": "A1",
    "order_type": "dine_in",
    "status": "open",
    "discount_amount": 0,
    "tax_amount": 5600,
    "notes": "",
    "ordered_at": "2026-06-04T10:00:00+07:00",
    "items": [
        {
            "product_guid": "33333333-3333-4333-8333-000000000001",
            "quantity": 2,
            "unit_price": 28000,
            "discount_amount": 0,
            "notes": null
        },
        {
            "product_guid": "33333333-3333-4333-8333-000000000018",
            "quantity": 1,
            "unit_price": 16000,
            "discount_amount": 0,
            "notes": null
        }
    ],
    "payments": [
        {
            "method": "cash",
            "status": "paid",
            "amount": 72000,
            "paid_at": "2026-06-04T10:05:00+07:00",
            "reference_number": "CASH-001",
            "notes": null
        }
    ]
}
```

### Validation

| Field | Rule |
|---|---|
| `order_number` | nullable, string, max:30, unique |
| `shift_guid` | nullable, string |
| `customer_name` | nullable, string, max:150 |
| `customer_phone` | nullable, string, max:30 |
| `table_number` | nullable, string, max:30 |
| `order_type` | nullable, string, in:dine_in,takeaway,delivery |
| `status` | nullable, string, in:draft,open,completed,cancelled |
| `discount_amount` | nullable, numeric, min:0 |
| `tax_amount` | nullable, numeric, min:0 |
| `notes` | nullable, string |
| `ordered_at` | nullable, date |
| `items` | **required**, array, min:1 |
| `items.*.product_guid` | required, string, exists:products |
| `items.*.quantity` | required, numeric, min:0.01 |
| `items.*.unit_price` | nullable, numeric, min:0 |
| `items.*.discount_amount` | nullable, numeric, min:0 |
| `items.*.notes` | nullable, string |
| `payments` | nullable, array |
| `payments.*.method` | required_with:payments, in:cash,debit_card,credit_card,qris,transfer,e_wallet |
| `payments.*.status` | nullable, in:pending,paid,failed,refunded |
| `payments.*.amount` | required_with:payments, numeric, min:0.01 |
| `payments.*.paid_at` | nullable, date |
| `payments.*.reference_number` | nullable, string, max:100 |
| `payments.*.notes` | nullable, string |

### Logic
1. Jika `shift_guid` dikirim, attach order ke shift aktif milik user
2. Hitung subtotal dari items (qty × price)
3. `total_amount = subtotal - discount + tax`
4. Payment status otomatis dihitung dari total payment amount: `unpaid` / `partial` / `paid`

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "d0000000-0000-4000-8000-000000000001",
            "order_number": "ORD-20260604100000-ABCD",
            "shift": {
                "guid": "55b7650b-1385-4785-afb6-4558e3fa09eb",
                "shift_number": "SH-SEED-20260604-001",
                "status": "open"
            },
            "cashier": {
                "guid": "b0000000-0000-4000-8000-000000000002",
                "full_name": "Dewi Lestari"
            },
            "customer_name": "Budi Santoso",
            "customer_phone": "081234567890",
            "table_number": "A1",
            "order_type": "dine_in",
            "status": "open",
            "payment_status": "paid",
            "subtotal": 72000,
            "discount_amount": 0,
            "tax_amount": 5600,
            "total_amount": 77600,
            "notes": "",
            "ordered_at": "2026-06-04T10:00:00.000000Z",
            "items": [
                {
                    "guid": "item-guid-new-1",
                    "product": {
                        "guid": "33333333-3333-4333-8333-000000000001",
                        "name": "Nasi Goreng Special"
                    },
                    "quantity": 2,
                    "unit_price": 28000,
                    "discount_amount": 0,
                    "subtotal": 56000,
                    "notes": null
                },
                {
                    "guid": "item-guid-new-2",
                    "product": {
                        "guid": "33333333-3333-4333-8333-000000000018",
                        "name": "Kopi Espresso"
                    },
                    "quantity": 1,
                    "unit_price": 16000,
                    "discount_amount": 0,
                    "subtotal": 16000,
                    "notes": null
                }
            ],
            "payments": [
                {
                    "guid": "payment-guid-new-1",
                    "payment_number": "PAY-20260604100500-ABCD",
                    "method": "cash",
                    "status": "paid",
                    "amount": 72000,
                    "paid_at": "2026-06-04T10:05:00.000000Z",
                    "reference_number": "CASH-001",
                    "notes": null
                }
            ],
            "created_at": "2026-06-04T10:00:00.000000Z",
            "updated_at": "2026-06-04T10:00:00.000000Z"
        },
        "message_en": "Order created successfully.",
        "message_id": "Order berhasil dibuat."
    }
}
```

### Error — Shift Not Found (409)

```json
{
    "response": {
        "code": "04",
        "status": "failed",
        "data": null,
        "message_en": "Active shift not found for this user.",
        "message_id": "Shift aktif untuk user ini tidak ditemukan."
    }
}
```

---

## 3. Show Order — `GET /orders/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "d0000000-0000-4000-8000-000000000001",
            "order_number": "ORD-RPT-20260604-001",
            "shift": null,
            "cashier": null,
            "customer_name": "Budi Santoso",
            "customer_phone": "081234567890",
            "table_number": "A1",
            "order_type": "dine_in",
            "status": "completed",
            "payment_status": "paid",
            "subtotal": 56000,
            "discount_amount": 0,
            "tax_amount": 5600,
            "total_amount": 61600,
            "notes": "Data seed report.",
            "ordered_at": "2026-06-04T08:07:00.000000Z",
            "items": [],
            "payments": [],
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
        "message_en": "Order not found.",
        "message_id": "Order tidak ditemukan."
    }
}
```

---

## 4. Update Order — `PUT /orders/update`

### Request Body

```json
{
    "guid": "d0000000-0000-4000-8000-000000000001",
    "customer_name": "Budi Santoso",
    "customer_phone": "081234567890",
    "table_number": "B2",
    "order_type": "dine_in",
    "status": "open",
    "discount_amount": 5000,
    "tax_amount": 5600,
    "notes": "Pindah meja",
    "ordered_at": "2026-06-04T10:00:00+07:00",
    "items": [
        {
            "product_guid": "33333333-3333-4333-8333-000000000001",
            "quantity": 2,
            "unit_price": 28000,
            "discount_amount": 0,
            "notes": null
        },
        {
            "product_guid": "33333333-3333-4333-8333-000000000021",
            "quantity": 1,
            "unit_price": 24000,
            "discount_amount": 0,
            "notes": null
        }
    ]
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `order_number` | nullable, string, max:30, unique (ignore self) |
| `customer_name` | nullable, string, max:150 |
| `customer_phone` | nullable, string, max:30 |
| `table_number` | nullable, string, max:30 |
| `order_type` | nullable, string, in:dine_in,takeaway,delivery |
| `status` | nullable, string, in:draft,open,completed,cancelled |
| `discount_amount` | nullable, numeric, min:0 |
| `tax_amount` | nullable, numeric, min:0 |
| `notes` | nullable, string |
| `ordered_at` | nullable, date |
| `items` | required, array, min:1 |

### Error — Completed Order (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Completed orders cannot be updated.",
        "message_id": "Order yang sudah selesai tidak dapat diperbarui."
    }
}
```

---

## 5. Delete Order — `DELETE /orders/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Order deleted successfully.",
        "message_id": "Order berhasil dihapus."
    }
}
```

### Error — Paid Order (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Paid orders cannot be deleted.",
        "message_id": "Order yang sudah dibayar tidak dapat dihapus."
    }
}
```

---

## Data Structures

### Order Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `order_number` | string | Format: `ORD-{YmdHis}-{random4}` |
| `shift` | object or null | `{ guid, shift_number, status }` |
| `cashier` | object or null | `{ guid, full_name }` |
| `customer_name` | string or null | Nama pelanggan |
| `customer_phone` | string or null | No. telepon |
| `table_number` | string or null | No. meja |
| `order_type` | string | `dine_in`, `takeaway`, atau `delivery` |
| `status` | string | `draft`, `open`, `completed`, atau `cancelled` |
| `payment_status` | string | `unpaid`, `partial`, `paid`, atau `refunded` |
| `subtotal` | float | SUM(quantity × unit_price) tiap item |
| `discount_amount` | float | Diskon |
| `tax_amount` | float | Pajak |
| `total_amount` | float | `subtotal - discount + tax` |
| `notes` | string or null | Catatan |
| `ordered_at` | string (ISO 8601) | Waktu order |
| `items` | array | Daftar item order |
| `payments` | array | Daftar pembayaran |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Order Item Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `product` | object | `{ guid, name }` |
| `quantity` | float | Jumlah |
| `unit_price` | float | Harga satuan |
| `discount_amount` | float | Diskon per item |
| `subtotal` | float | `(qty × price) - discount` |
| `notes` | string or null | Catatan item |

### Order Payment Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `payment_number` | string | Nomor pembayaran |
| `method` | string | `cash`, `debit_card`, `credit_card`, `qris`, `transfer`, `e_wallet` |
| `status` | string | `pending`, `paid`, `failed`, `refunded` |
| `amount` | float | Jumlah pembayaran |
| `paid_at` | string (ISO 8601) | Waktu bayar |
| `reference_number` | string or null | No. referensi |
| `notes` | string or null | Catatan |

### Payment Status Logic

| Kondisi | Status |
|---|---|
| Total paid = 0 | `unpaid` |
| 0 < Total paid < Total amount | `partial` |
| Total paid >= Total amount | `paid` |
