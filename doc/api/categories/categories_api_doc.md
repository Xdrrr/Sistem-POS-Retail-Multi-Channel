# Categories API Documentation

Base URL: `/categories`

Semua endpoint categories menggunakan middleware `EnsureApiToken`.

### Daftar Endpoint

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/categories` | EnsureApiToken | List kategori |
| `POST` | `/categories/store` | EnsureApiToken | Tambah kategori baru |
| `GET` | `/categories/{guid}` | EnsureApiToken | Detail kategori |
| `PUT` | `/categories/update` | EnsureApiToken | Update kategori |
| `DELETE` | `/categories/{guid}` | EnsureApiToken | Hapus kategori |

---

## 1. List Categories — `POST /categories`

Mendapatkan daftar kategori.

### Request Body (with filter)

```json
{
    "filter": {
        "set_guid": false,
        "guid": "11111111-1111-4111-8111-000000000001",
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
| `filter.set_is_active` | nullable, boolean |
| `filter.is_active` | nullable, boolean |
| `limit` | nullable, integer, min:1, max:100 (default: 20) |
| `page` | nullable, integer, min:1 (default: 1) |
| `order` | nullable, string, in:name,description,is_active,created_at,updated_at (default: name) |
| `sort` | nullable, string, in:ASC,DESC (default: ASC) |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": [
            {
                "guid": "11111111-1111-4111-8111-000000000001",
                "name": "makanan",
                "description": null,
                "image": null,
                "image_url": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            },
            {
                "guid": "11111111-1111-4111-8111-000000000002",
                "name": "minuman",
                "description": null,
                "image": null,
                "image_url": null,
                "is_active": true,
                "created_at": "2026-06-04T00:00:00.000000Z",
                "updated_at": "2026-06-04T00:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. Create Category — `POST /categories/store`

### Request Body

```json
{
    "name": "minuman dingin",
    "description": "Kategori minuman dingin",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `name` | required, string, max:100, unique |
| `description` | nullable, string |
| `image` | nullable, image file |
| `is_active` | nullable, boolean |

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "11111111-1111-4111-8111-000000000007",
            "name": "minuman dingin",
            "description": "Kategori minuman dingin",
            "image": null,
            "image_url": null,
            "is_active": true,
            "created_at": "2026-06-04T12:00:00.000000Z",
            "updated_at": "2026-06-04T12:00:00.000000Z"
        },
        "message_en": "Category created successfully.",
        "message_id": "Kategori berhasil dibuat."
    }
}
```

---

## 3. Show Category — `GET /categories/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "11111111-1111-4111-8111-000000000001",
            "name": "makanan",
            "description": null,
            "image": null,
            "image_url": null,
            "is_active": true,
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
        "message_en": "Category not found.",
        "message_id": "Kategori tidak ditemukan."
    }
}
```

---

## 4. Update Category — `PUT /categories/update`

### Request Body

```json
{
    "guid": "11111111-1111-4111-8111-000000000001",
    "name": "makanan berat",
    "description": "Kategori makanan berat",
    "is_active": true
}
```

### Validation

| Field | Rule |
|---|---|
| `guid` | required, string, exists |
| `name` | required, string, max:100, unique (ignore self) |
| `description` | nullable, string |
| `image` | nullable, image file |
| `is_active` | nullable, boolean |

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "11111111-1111-4111-8111-000000000001",
            "name": "makanan berat",
            "description": "Kategori makanan berat",
            "image": null,
            "image_url": null,
            "is_active": true,
            "created_at": "2026-06-04T00:00:00.000000Z",
            "updated_at": "2026-06-04T12:05:00.000000Z"
        },
        "message_en": "Category updated successfully.",
        "message_id": "Kategori berhasil diperbarui."
    }
}
```

---

## 5. Delete Category — `DELETE /categories/{guid}`

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": null,
        "message_en": "Category deleted successfully.",
        "message_id": "Kategori berhasil dihapus."
    }
}
```

### Error — Used by Products (409)

```json
{
    "response": {
        "code": "02",
        "status": "failed",
        "data": null,
        "message_en": "Category is used by product data.",
        "message_id": "Kategori masih digunakan oleh data produk."
    }
}
```

---

## Data Structures

### Category Object

| Field | Type | Description |
|---|---|---|
| `guid` | string (UUID) | Unique identifier |
| `name` | string | Nama kategori |
| `description` | string or null | Deskripsi |
| `image` | string or null | Path gambar |
| `image_url` | string or null | URL gambar |
| `is_active` | boolean | Status aktif |
| `created_at` | string (ISO 8601) | |
| `updated_at` | string (ISO 8601) | |

### Static GUIDs (from CatalogSeeder)

| Category | GUID |
|---|---|
| makanan | `11111111-1111-4111-8111-000000000001` |
| minuman | `11111111-1111-4111-8111-000000000002` |
| gorengan | `11111111-1111-4111-8111-000000000003` |
| dessert | `11111111-1111-4111-8111-000000000004` |
| paket hemat | `11111111-1111-4111-8111-000000000005` |
| snack | `11111111-1111-4111-8111-000000000006` |
