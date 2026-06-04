# Project Memory — POS SBY WIT (Boilerplate POS Backend)

## Stack
- Laravel 13 + PHP 8.3 + Inertia.js 3 + PostgreSQL
- Development environment: Laragon

## Arsitektur Aplikasi
Project ini terdiri dari **2 interface yang sharing 1 backend**:

| Interface | Target User | Akses | Fungsi |
|---|---|---|---|
| **Dashboard Web** | Owner/Manager/Admin | Browser via Inertia.js | Monitoring data, laporan, KPI |
| **Tablet POS App** | Kasir/Karyawan | API endpoints | Transaksi, order, payment |

### Dashboard Web (Project Ini)
- Diakses via Inertia page (Vue)
- Sidebar → link ke halaman order (`/orders`) untuk tambah order via web
- Khusus untuk **role non-karyawan** (owner/manager) — melihat data & monitoring sistem
- Report, grafik, KPI real-time

### Frontend Responsive System
- Responsive utama dashboard web dikelola secara global di `resources/css/app.css`.
- Global responsive mengatur scroll mobile/tablet untuk `html`, `body`, `#app`, `.dashboard-shell`, dan `.content`.
- Jangan mengunci page baru dengan `overflow: hidden`, `height: 100vh`, atau `height: calc(100vh...)` tanpa override mobile yang jelas, karena bisa membuat halaman HP tidak bisa scroll.
- Untuk page baru, gunakan pola wrapper yang sama: `AppSidebar`, `AppNavbar`, `.dashboard-shell`, dan `.content`, lalu biarkan responsive dasar mengikuti `resources/css/app.css`.
- Table atau daftar dengan kolom banyak harus memakai horizontal scroll (`.table-wrap` / `.table-scroll`) di mobile, bukan memaksa semua kolom masuk viewport.
- Modal di mobile harus bisa scroll; jangan membuat modal fixed-height tanpa overflow yang bisa digulir.
- Override responsive lokal di file Vue hanya boleh untuk kebutuhan spesifik komponen/page; aturan scroll, grid satu kolom, table overflow, dan modal scroll tetap bersumber dari global CSS.

### Tablet POS
- Tidak ada di repo ini (app terpisah)
- Konsumsi API backend yang sama
- API route sudah lengkap: `/api/orders/store`, `/api/payments/store`, dll.

## Role User (Fixed List)

| Role | Target User | Akses Dashboard | Akses API |
|---|---|---|---|
| **Superadmin** | Developer/IT | ✅ Full | ✅ Full |
| **Owner** | Pemilik usaha | ✅ Semua laporan & KPI | ✅ |
| **Manager** | Pengelola operasional | ✅ Laporan, shift, katalog | ✅ |
| **Cashier** | Kasir/karyawan | ❌ Tidak | ✅ Full (tablet POS) |
| **Users** | (default/legacy) | ❌ | ❌ |

### Catatan
- `Users` adalah role default sistem (is_default = true) — digunakan sebagai fallback
- `Cashier` adalah satu-satunya role yang TIDAK bisa akses dashboard web
- Otorisasi dashboard: `Superadmin`, `Owner`, `Manager` → boleh akses Inertia pages
- Otorisasi API: semua role kecuali `Users` boleh akses API endpoints

## Data Available for Reports

### Sales Report (Laporan Penjualan)
- **Table:** `orders.orders`
- **Fields:** order_number, customer_name, customer_phone, table_number, order_type (dine_in/takeaway/delivery), status (draft/open/completed/cancelled), payment_status (unpaid/partial/paid/refunded), subtotal, discount_amount, tax_amount, total_amount, ordered_at, notes

### Payment Report (Laporan Pembayaran)
- **Table:** `orders.payments`
- **Fields:** payment_number, method (cash/debit_card/credit_card/qris/transfer/e_wallet), status (pending/paid/failed/refunded), amount, paid_at, reference_number, notes

### Product Report (Laporan Produk)
- **Table:** `orders.order_items` + `product.products`
- **Fields:** product_name, quantity, unit_price, discount_amount, subtotal, notes
- **Related:** `product.categories` (name, description), `product.groups` (name, description)

### Financial Report (Laporan Keuangan)
- **From orders.orders:** subtotal, discount_amount, tax_amount, total_amount per time period

### Customer Report
- **From orders.orders:** customer_name, customer_phone, order count, total spent

### Order Status Report
- **From orders.orders:** breakdown by status and payment_status

### Catalog Data
- **Tables:** `product.products`, `product.categories`, `product.groups`
- **Fields:** name, price, is_active, description
- Seeder catalog wajib menjaga image terkait dengan data seeder. Dummy image seed disimpan di `storage/app/public/catalog/seed/{categories|groups|products}/` dan path DB memakai format `catalog/seed/{folder}/{Str::slug(name)}.png`.
- Jika menambah/mengubah item di `CatalogSeeder`, tambahkan/update PNG dengan slug nama yang sama agar data seed tidak menghasilkan broken image.

### Pre-computed KPIs (HomePageController)
- sales_total, cash_total, digital_total, transactions_today, active_shift, pending_payments, completed_orders, hourly_sales, recent_orders

## Report Filter Design

### Filter per Report

| Report | Filter | Tipe Input | Kolom DB |
|---|---|---|---|
| **Semua (kecuali Katalog)** | Periode Tanggal | Date Range | `ordered_at` / `paid_at` |
| **Lap. Penjualan** | Status Order | Multi-select | `status` |
| | Tipe Pesanan | Multi-select | `order_type` |
| | Status Pembayaran | Multi-select | `payment_status` |
| | Nama Pelanggan | Text search | `customer_name`, `customer_phone` |
| **Lap. Pembayaran** | Metode Pembayaran | Multi-select | `method` |
| | Status Pembayaran | Multi-select | `status` |
| **Lap. Produk** | Kategori | Multi-select | `product.categories.guid` |
| | Grup | Multi-select | `product.groups.guid` |
| | Nama Produk | Text search | `product.name` |
| **Lap. Keuangan** | Status Order | Multi-select | `status` |
| | Status Pembayaran | Multi-select | `payment_status` |
| **Lap. Customer** | Nama Customer | Text search | `customer_name` |
| | No. Telepon | Text | `customer_phone` |
| | Min. Transaksi | Number | COUNT orders |
| | Min. Total Belanja | Number | SUM total_amount |
| **Lap. Status Order** | Status Order | Multi-select | `status` |
| | Status Pembayaran | Multi-select | `payment_status` |
| **Lap. Katalog** | Kategori | Multi-select | `category_guid` |
| | Grup | Multi-select | `group_guid` |
| | Status Produk | Single-select | `is_active` |

### Filter Pattern (Set-True)
Filter menggunakan prefix `set_` sebagai boolean flag:

```json
{
  "filter": {
    "set_guid": true,
    "guid": "b83dee8f-4415-4abf-b94f-9bcf4054f150",
    "set_category_id": false,
    "category_id": 1,
    "set_group_id": false,
    "group_id": 2
  },
  "limit": 20,
  "page": 1,
  "order": "name",
  "sort": "ASC"
}
```

- `set_{field}` = `true` → filter aktif, query WHERE pakai nilai `{field}`
- `set_{field}` = `false` → filter tidak dipakai (ignore)
- Semua endpoint list API wajib memakai payload standar `{ "filter": { "set_{field}": boolean, "{field}": value }, "limit": 20, "page": 1, "order": "...", "sort": "ASC|DESC" }`; jangan menaruh field filter langsung di root payload.
- Untuk CRUD/list sederhana tetap boleh memakai `app/Traits/Filterable.php` method `applyFilter()`
- Untuk report besar, buat filter khusus di service report; jangan bergantung pada `Filterable` karena report butuh date range, multi-select, search `ILIKE`, aggregate `HAVING`, dan whitelist order column

### Catatan
- Filter periode tanggal: semua report kecuali **Laporan Katalog** (data master statis)
- Kolom yg dipakai untuk filter tanggal:
  - `orders.orders.ordered_at` → Penjualan, Produk, Keuangan, Customer, Status Order
  - `orders.payments.paid_at` → Pembayaran
- Katalog pakai filter: Status (active/inactive), Kategori, Grup saja

### Report Module Design

#### Struktur yang disarankan
- `app/Http/Controllers/ReportPageController.php` → render halaman Inertia report
- `app/Http/Controllers/ReportController.php` → endpoint JSON report/export via web route `/reports/*`
- `app/Services/Reports/SalesReportQuery.php`
- `app/Services/Reports/PaymentReportQuery.php`
- `app/Services/Reports/ProductReportQuery.php`
- `app/Services/Reports/FinancialReportQuery.php`
- `app/Services/Reports/CustomerReportQuery.php`
- `app/Services/Reports/OrderStatusReportQuery.php`
- `app/Services/Reports/CatalogReportQuery.php`
- `app/Jobs/ExportReportJob.php`
- `app/Models/ReportExport.php` atau table metadata export sejenis untuk tracking status file

#### Endpoint Report (tanpa prefix `/api`)
| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/reports` | Halaman Inertia report |
| `POST` | `/reports/{type}/preview` | Preview/list data report dengan filter dan pagination |
| `POST` | `/reports/{type}/summary` | Agregat/KPI ringkas untuk report |
| `POST` | `/reports/{type}/export` | Buat job export XLSX/CSV |
| `GET` | `/reports/exports/{guid}` | Cek status export: queued/processing/done/failed |
| `GET` | `/reports/exports/{guid}/download` | Download file hasil export |

#### Pattern Query
- Controller hanya validasi request dan memanggil service query
- Service query menyediakan method `baseQuery()`, `applyFilters()`, `preview()`, `summary()`, dan `exportRows()`
- Preview/page view menggunakan `paginate()` atau limit kecil
- Export menggunakan streaming query, bukan mengambil semua data ke memory
- Query agregat/KPI dihitung langsung di SQL dengan `SUM`, `COUNT`, `GROUP BY`, bukan `->get()->sum()` di collection
- Whitelist kolom sorting dengan map, jangan langsung `orderBy($request->order)`

### Query Optimization

#### Index Strategy (PostgreSQL)
```sql
-- WAJIB: untuk range query tanggal
CREATE INDEX idx_orders_ordered_at ON orders.orders(ordered_at);
CREATE INDEX idx_payments_paid_at ON orders.payments(paid_at);

-- WAJIB: untuk filter status
CREATE INDEX idx_orders_status ON orders.orders(status);
CREATE INDEX idx_orders_payment_status ON orders.orders(payment_status);
CREATE INDEX idx_payments_method ON orders.payments(method);
CREATE INDEX idx_payments_status ON orders.payments(status);

-- Partial index: hanya completed yang relevan untuk report
CREATE INDEX idx_orders_completed_ordered_at ON orders.orders(ordered_at) WHERE status = 'completed';

-- Composite index untuk query umum
CREATE INDEX idx_orders_status_ordered_at ON orders.orders(status, ordered_at);
CREATE INDEX idx_orders_payment_status_ordered_at ON orders.orders(payment_status, ordered_at);
CREATE INDEX idx_orders_order_type_ordered_at ON orders.orders(order_type, ordered_at);
CREATE INDEX idx_payments_status_paid_at ON orders.payments(status, paid_at);
CREATE INDEX idx_payments_method_paid_at ON orders.payments(method, paid_at);
CREATE INDEX idx_order_items_order_guid ON orders.order_items(order_guid);
CREATE INDEX idx_order_items_product_guid ON orders.order_items(product_guid);
CREATE INDEX idx_products_category_guid ON product.products(category_guid);
CREATE INDEX idx_products_group_guid ON product.products(group_guid);
```

#### Search Index (opsional jika data besar)
```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX idx_orders_customer_name_trgm ON orders.orders USING gin (customer_name gin_trgm_ops);
CREATE INDEX idx_orders_customer_phone_trgm ON orders.orders USING gin (customer_phone gin_trgm_ops);
CREATE INDEX idx_products_name_trgm ON product.products USING gin (name gin_trgm_ops);
```

#### Query Pattern (Chunking)
```php
// WAJIB untuk export: lazyById/chunkById, jangan ->get() atau ->all()
// Untuk export besar, prefer Query Builder + select eksplisit agar tidak hydrate model Eloquent.
DB::table('orders.orders as o')
    ->select([
        'o.id',
        'o.order_number',
        'o.customer_name',
        'o.order_type',
        'o.status',
        'o.payment_status',
        'o.total_amount',
        'o.ordered_at',
    ])
    ->whereBetween('o.ordered_at', [$from, $to])
    ->whereIn('o.status', $statuses)
    ->orderBy('o.id')
    ->lazyById(500, 'o.id')
    ->each(function ($order) use ($writer) {
            $writer->addRow([...]);
    });
```

#### Catatan Join + Chunk
- Sales export: chunk/lazy by `orders.orders.id`
- Payment export: chunk/lazy by `orders.payments.id`
- Product export: chunk/lazy by `orders.order_items.id`
- Pada query join, selalu pakai alias kolom id yang jelas agar tidak bentrok

#### Export Architecture
- **Library:** OpenSpout (`openspout/openspout`) — streaming writer, memory fixed
- **Queue:** Process via Job biar user ga nunggu
- **File:** Simpan sementara di `storage/app/reports/`, cleanup scheduler
- **Cache:** Cache query agregat, bukan raw data
- **Preview vs Export:** preview pakai pagination kecil, export selalu async via job
- **Storage Metadata:** simpan status export, filter JSON, file path, row count, error message, requested_by, expired_at

## Shift Feature Plan - Build From Scratch

Status memori: fitur shift dianggap belum ada. Implementasi berikutnya harus dimulai dari migration, model, service, controller, route, integrasi order, lalu halaman monitoring.

### Tujuan
- Cashier membuka dan menutup shift dari Tablet POS.
- Frontend/tablet mengirim `work_hours` sebagai data kerja shift. Backend menyimpan nilai ini apa adanya, lalu boleh memvalidasi konsistensi dengan `opened_at` dan `closed_at`.
- Backend menghitung pendapatan sales per shift dari order yang terhubung ke shift tersebut.
- Order direlasikan ke shift lewat `shift_guid` pada request dan `shift_id` di database.
- Relasi code dibuat dua arah: `Shift hasMany Order`, `Order belongsTo Shift`.

### Prinsip Workflow
- Controller tipis: validasi request, ambil user dari token/session, panggil service.
- Logic shift ada di `app/Services/Shifts/ShiftService.php`.
- Query summary shift ada di `app/Services/Shifts/ShiftSalesSummary.php` agar ringan dan reusable.
- Summary sales dihitung dengan SQL aggregate, bukan collection `get()->sum()`.
- Timestamp dan `work_hours` berasal dari frontend/tablet. Server tidak membuat durasi memakai `now()` untuk data bisnis.
- Satu cashier hanya boleh punya satu shift berstatus `open`.

### Database Plan
```sql
CREATE TABLE authentication.shifts (
    id               BIGSERIAL PRIMARY KEY,
    guid             UUID UNIQUE NOT NULL,
    user_id          BIGINT NOT NULL REFERENCES authentication.users(id),
    user_guid        UUID NOT NULL REFERENCES authentication.users(guid),
    shift_number     VARCHAR(30) UNIQUE NOT NULL,
    opened_at        TIMESTAMP NOT NULL,
    closed_at        TIMESTAMP NULL,
    work_hours       DECIMAL(8,2) NOT NULL DEFAULT 0,
    opening_balance  DECIMAL(15,2) NOT NULL DEFAULT 0,
    closing_balance  DECIMAL(15,2) NULL,
    expected_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    difference       DECIMAL(15,2) NULL,
    notes            TEXT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);

ALTER TABLE orders.orders ADD COLUMN shift_id BIGINT NULL REFERENCES authentication.shifts(id);
ALTER TABLE orders.orders ADD COLUMN user_id BIGINT NULL REFERENCES authentication.users(id);
```

### Model Relation Plan
```php
// app/Models/Shift.php
public function orders()
{
    return $this->hasMany(Order::class, 'shift_id', 'id');
}

public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'id');
}

// app/Models/Order.php
public function shift()
{
    return $this->belongsTo(Shift::class, 'shift_id', 'id');
}

public function cashier()
{
    return $this->belongsTo(User::class, 'user_id', 'id');
}
```

### API Endpoints

| Method | Endpoint | Auth | Fungsi |
|---|---|---|---|
| `POST` | `/shift/store` | EnsureApiToken | Buka shift baru |
| `PUT` | `/shift/close` | EnsureApiToken | Tutup shift |
| `GET` | `/shift/active` | EnsureApiToken | Cek shift active user ini |
| `GET` | `/shift/{guid}` | EnsureApiToken | Detail shift (summary) |
| `POST` | `/shift` | EnsureApiToken | List shift (filter) |

### 1. Open Shift - `POST /shift/store`

**Request:**
```json
{
    "opened_at": "2026-06-03T08:00:00+07:00",
    "work_hours": 8,
    "opening_balance": 500000,
    "notes": "Shift pagi"
}
```

**Validation:**
| Field | Rule |
|---|---|
| `opened_at` | required, date (ISO 8601 from tablet) |
| `work_hours` | required, numeric, min:0.25, max:24 |
| `opening_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

**Logic:**
1. Ambil user dari API token.
2. Tolak jika user sudah punya shift `open`.
3. Generate `shift_number`: `SH-{Ymd}-{NNN}` berdasarkan tanggal `opened_at`.
4. Simpan `opened_at`, `work_hours`, dan `opening_balance` dari frontend.
5. Set `expected_balance = opening_balance`, status `open`.

**Response (201):**
```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-shift",
            "shift_number": "SH-20260603-001",
            "user": {
                "guid": "uuid-user",
                "full_name": "Ahmad"
            },
            "opened_at": "2026-06-03T08:00:00+07:00",
            "work_hours": 8,
            "opening_balance": 500000,
            "expected_balance": 500000,
            "status": "open",
            "notes": "Shift pagi"
        },
        "message_en": "Shift opened successfully.",
        "message_id": "Shift berhasil dibuka."
    }
}
```

### 2. Close Shift - `PUT /shift/close`

**Request:**
```json
{
    "guid": "uuid-shift",
    "closed_at": "2026-06-03T16:00:00+07:00",
    "work_hours": 8,
    "closing_balance": 2500000,
    "notes": "Shift selesai lancar"
}
```

**Validation:**
| Field | Rule |
|---|---|
| `guid` | required, string, exists:shifts |
| `closed_at` | required, date (ISO 8601 from tablet) |
| `work_hours` | required, numeric, min:0.25, max:24 |
| `closing_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

**Logic:**
1. Cari shift berdasarkan `guid`, user login, dan status `open`.
2. Simpan `closed_at` dan `work_hours` dari frontend.
3. Hitung summary sales dari order yang punya `shift_id`.
4. `expected_balance = opening_balance + cash_sales`.
5. `difference = closing_balance - expected_balance`.
6. Set status `closed` dan kembalikan summary shift.

**Response (200):**
```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-shift",
            "shift_number": "SH-20260603-001",
            "user": {
                "guid": "uuid-user",
                "full_name": "Ahmad"
            },
            "opened_at": "2026-06-03T08:00:00+07:00",
            "closed_at": "2026-06-03T16:00:00+07:00",
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
                "order_count": 24
            }
        },
        "message_en": "Shift closed successfully.",
        "message_id": "Shift berhasil ditutup."
    }
}
```

**Catatan `difference`**: nilai positif = lebih (uang fisik lebih banyak dari seharusnya), negatif = kurang.

### 3. Active Shift - `GET /shift/active`

**Logic:**
1. Cari shift milik user ini dengan status `open`
2. Jika tidak ada: return null (boleh buka shift baru)

**Response (200):**
```json
{
    "response": {
        "code": "00",
        "status": "success",
        "data": {
            "guid": "uuid-shift",
            "shift_number": "SH-20260603-001",
            "opened_at": "2026-06-03T08:00:00+07:00",
            "work_hours": 8,
            "opening_balance": 500000,
            "expected_balance": 2400000,
            "status": "open",
            "summary": {
                "total_sales": 2100000,
                "cash_sales": 1900000,
                "digital_sales": 200000,
                "order_count": 24
            }
        }
    }
}
```

### 4. Detail Shift - `GET /shift/{guid}`

**Logic:**
1. Load shift + user info.
2. Hitung summary sales dari order yang punya `shift_id`.
3. Tampilkan `work_hours`, balance, summary, dan order terbaru.
4. Jika status `closed`: tampilkan closing_balance, expected_balance, difference.

**Response (200):** Full data shift + summary + daftar orders (terbaru, max 50).

### 5. List Shift - `POST /shift`

**Request:**
```json
{
    "filter": {
        "set_guid": false,
        "guid": "uuid-shift",
        "set_status": true,
        "status": "closed",
        "set_user_guid": true,
        "user_guid": "uuid-user",
        "set_from_date": true,
        "from_date": "2026-06-01",
        "set_to_date": true,
        "to_date": "2026-06-03"
    },
    "limit": 20,
    "page": 1,
    "order": "opened_at",
    "sort": "DESC"
}
```

**Validation:**
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
| `limit` | nullable, integer, min:1, max:100 |
| `page` | nullable, integer, min:1 |
| `order` | nullable, string, in:shift_number,opened_at,closed_at,created_at |
| `sort` | nullable, string, in:ASC,DESC |

### Integrasi dengan Order (Tablet)

**`POST /api/orders/store`** menerima field `shift_guid` opsional:
```json
{
    "shift_guid": "uuid-shift",
    "items": [...],
    "payments": [...]
}
```
- Jika `shift_guid` dikirim, cari shift milik user login dengan status `open`.
- Simpan `orders.orders.shift_id = shifts.id`.
- Simpan `orders.orders.user_id = authenticated user id`.
- Jika `shift_guid` tidak dikirim, order boleh dibuat tanpa shift sesuai kebutuhan bisnis.
- Detail order response menampilkan `shift_guid` dan `shift_number` jika ada.

### Shift Sales Summary
Summary dihitung dari `orders.orders` join `orders.payments`, dibatasi oleh `shift_id`, bukan hanya range waktu.

Output minimal:
```json
{
  "total_sales": 2100000,
  "cash_sales": 1900000,
  "digital_sales": 200000,
  "order_count": 24,
  "paid_order_count": 23,
  "pending_payment_count": 1
}
```

Query guideline:
- `total_sales`: SUM `orders.total_amount` untuk order status `completed`.
- `cash_sales`: SUM payment amount method `cash` dan status `paid`.
- `digital_sales`: SUM payment amount method selain `cash` dan status `paid`.
- `order_count`: COUNT distinct orders pada shift.
- Gunakan Query Builder dengan select aggregate dan `COALESCE`.

### Dashboard Web - Shift Monitoring

| Halaman | Route | Fungsi |
|---|---|---|
| Shift index | `GET /shifts` | Lihat semua shift (active + history) |
| Shift detail | `GET /shifts/{guid}` | Detail shift, daftar order, rekap payment |
| Dashboard | `GET /` | Card active shifts (jumlah kasir aktif, durasi, sales) |

### Suggested Files
- `database/migrations/*_create_authentication_shifts_table.php`
- `database/migrations/*_add_shift_id_and_user_id_to_orders_table.php`
- `app/Models/Shift.php`
- `app/Services/Shifts/ShiftService.php`
- `app/Services/Shifts/ShiftSalesSummary.php`
- `app/Http/Controllers/ShiftApiController.php`
- `app/Http/Controllers/ShiftPageController.php`
- `resources/js/Pages/Shift/Index.vue`
- `resources/js/Pages/Shift/Show.vue`

### Saran Index PostgreSQL
```sql
CREATE INDEX idx_shifts_user_id ON authentication.shifts(user_id);
CREATE INDEX idx_shifts_user_guid ON authentication.shifts(user_guid);
CREATE INDEX idx_shifts_status ON authentication.shifts(status);
CREATE INDEX idx_shifts_opened_at ON authentication.shifts(opened_at);
CREATE INDEX idx_shifts_status_user_id ON authentication.shifts(status, user_id);
CREATE INDEX idx_orders_shift_id ON orders.orders(shift_id);
CREATE INDEX idx_orders_user_id ON orders.orders(user_id);
CREATE INDEX idx_orders_shift_status ON orders.orders(shift_id, status);
```
