# Reports API Documentation

Base URL: `/reports`

Endpoint report memakai web route dengan session dashboard dan CSRF token. Endpoint ini tidak memakai prefix `/api`.

### Daftar Endpoint

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/reports` | Halaman Inertia report |
| `GET` | `/reports/exports` | Halaman riwayat export |
| `POST` | `/reports/exports/history` | List riwayat export |
| `POST` | `/reports/{type}/preview` | Preview data report |
| `POST` | `/reports/{type}/summary` | Summary/agregat report |
| `POST` | `/reports/{type}/export` | Queue export CSV |
| `GET` | `/reports/exports/{guid}` | Cek status export |
| `GET` | `/reports/exports/{guid}/download` | Download file export |

### Report Types

| Type | Judul |
|---|---|
| `sales` | Laporan Penjualan |
| `payments` | Laporan Pembayaran |
| `products` | Laporan Produk |
| `financial` | Laporan Keuangan |
| `customers` | Laporan Customer |
| `status` | Laporan Status Order |
| `catalog` | Laporan Katalog |

---

## 1. Preview Report - `POST /reports/{type}/preview`

Mengembalikan kolom, data preview, dan metadata pagination.

### Request Body

```json
{
    "filter": {
        "set_from_date": true,
        "from_date": "2026-06-04T00:00",
        "set_to_date": true,
        "to_date": "2026-06-04T23:59",
        "set_statuses": true,
        "statuses": ["completed"],
        "set_payment_statuses": false,
        "payment_statuses": []
    },
    "limit": 20,
    "page": 1,
    "order": "ordered_at",
    "sort": "DESC"
}
```

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "columns": ["order_number", "customer", "order_type", "status", "payment_status", "subtotal", "discount_amount", "tax_amount", "total_amount", "ordered_at"],
            "data": [],
            "meta": {
                "current_page": 1,
                "last_page": 1,
                "per_page": 20,
                "total": 0
            }
        }
    }
}
```

---

## 2. Summary Report - `POST /reports/{type}/summary`

Mengembalikan agregat/KPI sesuai tipe report dan filter yang sama dengan preview.

### Response (200) Example

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "order_count": 24,
            "subtotal": 2100000,
            "discount_amount": 0,
            "tax_amount": 210000,
            "total_amount": 2310000
        }
    }
}
```

---

## 3. Export Report - `POST /reports/{type}/export`

Membuat job export CSV async. File disimpan di `storage/app/reports/{guid}.csv`.

### Response (201)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "550e8400-e29b-41d4-a716-446655440000",
            "type": "sales",
            "status": "queued",
            "format": "csv",
            "row_count": 0,
            "error_message": null,
            "filters": {
                "set_from_date": true,
                "from_date": "2026-06-04T00:00"
            },
            "created_at": "2026-06-04T08:00:00.000000Z",
            "started_at": null,
            "finished_at": null,
            "download_url": null
        },
        "message_en": "Report export queued.",
        "message_id": "Export laporan masuk antrean."
    }
}
```

---

## 4. Export Status - `GET /reports/exports/{guid}`

Status yang mungkin: `queued`, `processing`, `done`, `failed`.

Jika status `done`, field `download_url` berisi URL download file.

---

## 5. Export History - `POST /reports/exports/history`

Mengambil riwayat export dengan pagination.

### Request Body

```json
{
    "filter": {
        "set_type": true,
        "type": "sales",
        "set_status": true,
        "status": "done",
        "set_from_date": false,
        "from_date": "",
        "set_to_date": false,
        "to_date": ""
    },
    "limit": 10,
    "page": 1
}
```

### Response (200)

```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "data": [],
            "meta": {
                "current_page": 1,
                "last_page": 1,
                "per_page": 10,
                "total": 0
            }
        }
    }
}
```

---

## Filter Per Report

Semua filter memakai pola `set_*`. Jika `set_{field}` bernilai `false`, field tersebut diabaikan.

| Report | Filter |
|---|---|
| `sales` | `from_date`, `to_date`, `statuses`, `order_types`, `payment_statuses`, `customer_search` |
| `payments` | `from_date`, `to_date`, `methods`, `statuses` |
| `products` | `from_date`, `to_date`, `category_guids`, `group_guids`, `statuses`, `product_search` |
| `financial` | `from_date`, `to_date`, `statuses`, `payment_statuses` |
| `customers` | `from_date`, `to_date`, `customer_search`, `customer_phone`, `min_transactions`, `min_total_spent` |
| `status` | `from_date`, `to_date`, `statuses`, `payment_statuses` |
| `catalog` | `category_guids`, `group_guids`, `product_search`, `is_active` |

### Catatan Implementasi

- Export memakai `ExportReportJob` dan format CSV (`fputcsv`).
- Metadata export tersimpan di table `report_exports`.
- Export sukses mengisi `file_path`, `row_count`, `finished_at`, dan `expired_at` 7 hari setelah selesai.
- Query report memakai Query Builder dan whitelist sorting di setiap service report.
