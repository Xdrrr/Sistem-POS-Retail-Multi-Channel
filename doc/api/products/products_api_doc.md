# Products API Documentation

Base URL: `/products`

Semua endpoint products menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/products` | EnsureApiToken | List produk |
| `POST` | `/products/store` | EnsureApiToken | Tambah produk baru |
| `GET` | `/products/{guid}` | EnsureApiToken | Detail produk |
| `PUT` | `/products/update` | EnsureApiToken | Update produk |
| `DELETE` | `/products/{guid}` | EnsureApiToken | Hapus produk |

---

## 1. List Products — `POST /products`

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "33333333-3333-4333-8333-000000000001",
        "set_category_guid": true,
        "category_guid": "11111111-1111-4111-8111-000000000001",
        "set_group_guid": false,
        "group_guid": "22222222-2222-4222-8222-000000000002",
        "set_is_active": true,
        "is_active": true
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
| `filter` | nullable, array |
| `filter.set_guid` | nullable, boolean |
| `filter.guid` | nullable, string |
| `filter.set_category_guid` | nullable, boolean |
| `filter.category_guid` | nullable, string |
| `filter.set_group_guid` | nullable, boolean |
| `filter.group_guid` | nullable, string |
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:name,description,price,is_active,created_at,updated_at (default: name) |
| `sort` | nullable, string, in:ASC,DESC (default: ASC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "33333333-3333-4333-8333-000000000001",
                "name": "Nasi Goreng Special",
                "description": null,
                "image": null,
                "image_url": null,
                "price": 28000,
                "is_active": true,
                "category": {
                    "guid": "11111111-1111-4111-8111-000000000001",
                    "name": "makanan"
                },
                "group": {
                    "guid": "22222222-2222-4222-8222-000000000002",
                    "name": "nasi"
                },
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            },
            {
                "guid": "33333333-3333-4333-8333-000000000018",
                "name": "Kopi Espresso",
                "description": null,
                "image": null,
                "image_url": null,
                "price": 16000,
                "is_active": true,
                "category": {
                    "guid": "11111111-1111-4111-8111-000000000002",
                    "name": "minuman"
                },
                "group": {
                    "guid": "22222222-2222-4222-8222-000000000001",
                    "name": "kopi"
                },
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Product — `POST /products/store`

### Request Body

```json
{
    "category_guid": "11111111-1111-4111-8111-000000000001",
    "group_guid": "22222222-2222-4222-8222-000000000002",
    "name": "Nasi Goreng Seafood",
    "description": "Nasi goreng dengan seafood fresh",
    "price": 35000,
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `category_guid` | required, string, exists:categories |
| `group_guid` | required, string, exists:groups |
| `name` | required, string, max:150, unique |
| `description` | nullable, string |
| `image` | nullable, image file |
| `price` | nullable, numeric, min:0 (default: 0) |
| `is_active` | nullable, boolean (default: true) |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "33333333-3333-4333-8333-000000000032",
            "name": "Nasi Goreng Seafood",
            "description": "Nasi goreng dengan seafood fresh",
            "image": null,
            "image_url": null,
            "price": 35000,
            "is_active": true,
            "category": {
                "guid": "11111111-1111-4111-8111-000000000001",
                "name": "makanan"
            },
            "group": {
                "guid": "22222222-2222-4222-8222-000000000002",
                "name": "nasi"
            },
            "created_at": "2026-06-04T12:00:00.000000Z",
            "updated_at": "2026-06-04T12:00:00.000000Z"
        },
        "message_en": "Product created successfully.",
        "message_id": "Produk berhasil dibuat."
    }
}
```

---

## 3. Show Product — `GET /products/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "33333333-3333-4333-8333-000000000001",
            "name": "Nasi Goreng Special",
            "description": null,
            "image": null,
            "image_url": null,
            "price": 28000,
            "is_active": true,
            "category": {
                "guid": "11111111-1111-4111-8111-000000000001",
                "name": "makanan"
            },
            "group": {
                "guid": "22222222-2222-4222-8222-000000000002",
                "name": "nasi"
            },
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
        "message_en": "Product not found.",
        "message_id": "Produk tidak ditemukan."
    }
}
```

---

## 4. Update Product — `PUT /products/update`

### Request Body

```json
{
    "guid": "33333333-3333-4333-8333-000000000001",
    "category_guid": "11111111-1111-4111-8111-000000000001",
    "group_guid": "22222222-2222-4222-8222-000000000002",
    "name": "Nasi Goreng Special",
    "description": "Nasi goreng special with extra topping",
    "price": 32000,
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `category_guid` | required, string, exists |
| `group_guid` | required, string, exists |
| `name` | required, string, max:150, unique (ignore self) |
| `description` | nullable, string |
| `image` | nullable, image file |
| `price` | nullable, numeric, min:0 |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "33333333-3333-4333-8333-000000000001",
            "name": "Nasi Goreng Special",
            "description": "Nasi goreng special with extra topping",
            "image": null,
            "image_url": null,
            "price": 32000,
            "is_active": true,
            "category": {
                "guid": "11111111-1111-4111-8111-000000000001",
                "name": "makanan"
            },
            "group": {
                "guid": "22222222-2222-4222-8222-000000000002",
                "name": "nasi"
            },
            "created_at": "2026-06-04T00:00:00.000000Z",
            "updated_at": "2026-06-04T12:05:00.000000Z"
        },
        "message_en": "Product updated successfully.",
        "message_id": "Produk berhasil diperbarui."
    }
}
```

---

## 5. Delete Product — `DELETE /products/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Product deleted successfully.",
        "message_id": "Produk berhasil dihapus."
    }
}
```

---

## Data Structures

### Product Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `name` | string | Nama produk |
| `description` | string or null | Deskripsi |
| `image` | string or null | Path gambar |
| `image_url` | string or null | URL gambar |
| `price` | float | Harga produk |
| `is_active` | boolean | Status aktif |
| `category` | object | `{ guid, name }` |
| `group` | object | `{ guid, name }` |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Static GUIDs (from CatalogSeeder)

| Product Name | GUID |
|---|---|
| Nasi Goreng Special | `33333333-3333-4333-8333-000000000001` |
| Nasi Goreng Kampung | `33333333-3333-4333-8333-000000000002` |
| Nasi Ayam Geprek | `33333333-3333-4333-8333-000000000003` |
| Nasi Ayam Katsu | `33333333-3333-4333-8333-000000000004` |
| Nasi Telur Sambal Matah | `33333333-3333-4333-8333-000000000005` |
| Mi Goreng Jawa | `33333333-3333-4333-8333-000000000006` |
| Mi Rebus Soto | `33333333-3333-4333-8333-000000000007` |
| Spaghetti Aglio Olio | `33333333-3333-4333-8333-000000000008` |
| Spaghetti Bolognese | `33333333-3333-4333-8333-000000000009` |
| Chicken Popcorn | `33333333-3333-4333-8333-000000000010` |
| Kentang Goreng | `33333333-3333-4333-8333-000000000011` |
| Pisang Goreng Coklat | `33333333-3333-4333-8333-000000000012` |
| Tahu Crispy | `33333333-3333-4333-8333-000000000013` |
| Roti Bakar Coklat | `33333333-3333-4333-8333-000000000014` |
| Roti Bakar Keju | `33333333-3333-4333-8333-000000000015` |
| Brownies Slice | `33333333-3333-4333-8333-000000000016` |
| Cheesecake Mini | `33333333-3333-4333-8333-000000000017` |
| Kopi Espresso | `33333333-3333-4333-8333-000000000018` |
| Kopi Esspresso | `33333333-3333-4333-8333-000000000019` |
| Kopi Americano | `33333333-3333-4333-8333-000000000020` |
| Kopi Latte | `33333333-3333-4333-8333-000000000021` |
| Kopi Cappuccino | `33333333-3333-4333-8333-000000000022` |
| Es Kopi Susu Gula Aren | `33333333-3333-4333-8333-000000000023` |
| Teh Manis Panas | `33333333-3333-4333-8333-000000000024` |
| Es Teh Lemon | `33333333-3333-4333-8333-000000000025` |
| Thai Tea | `33333333-3333-4333-8333-000000000026` |
| Jus Alpukat | `33333333-3333-4333-8333-000000000027` |
| Jus Jeruk | `33333333-3333-4333-8333-000000000028` |
| Paket Ayam Geprek | `33333333-3333-4333-8333-000000000029` |
| Paket Nasi Goreng Kopi | `33333333-3333-4333-8333-000000000030` |
| Paket Katsu Tea | `33333333-3333-4333-8333-000000000031` |
