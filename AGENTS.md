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
- Implementasi: `app/Traits/Filterable.php` method `applyFilter()`

### Catatan
- Filter periode tanggal: semua report kecuali **Laporan Katalog** (data master statis)
- Kolom yg dipakai untuk filter tanggal:
  - `orders.orders.ordered_at` → Penjualan, Produk, Keuangan, Customer, Status Order
  - `orders.payments.paid_at` → Pembayaran
- Katalog pakai filter: Status (active/inactive), Kategori, Grup saja

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
```

#### Query Pattern (Chunking)
```php
// WAJIB: chunkById + lazy collection, jangan ->get() atau ->all()
Order::query()
    ->whereBetween('ordered_at', [$from, $to])
    ->whereIn('status', $statuses)
    ->orderBy('id')
    ->chunkById(500, function ($orders) use ($writer) {
        foreach ($orders as $order) {
            $writer->addRow([...]);
        }
    });
```

#### Export Architecture
- **Library:** OpenSpout (`openspout/openspout`) — streaming writer, memory fixed
- **Queue:** Process via Job biar user ga nunggu
- **File:** Simpan sementara di `storage/app/reports/`, cleanup scheduler
- **Cache:** Cache query agregat, bukan raw data

## Shift Feature — API Spec for Tablet

### Tabel shifts
```sql
CREATE TABLE authentication.shifts (
    id              BIGSERIAL PRIMARY KEY,
    guid            UUID UNIQUE NOT NULL,
    user_guid       UUID NOT NULL REFERENCES authentication.users(guid),
    shift_number    VARCHAR(30) UNIQUE NOT NULL,       -- SH-20260603-001
    opened_at       TIMESTAMP NOT NULL,                  -- dari tablet (client timestamp)
    closed_at       TIMESTAMP,                           -- dari tablet (client timestamp)
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    closing_balance DECIMAL(15,2),                       -- diisi saat tutup shift
    expected_balance DECIMAL(15,2),                      -- opening + cash sales
    difference      DECIMAL(15,2),                       -- closing - expected
    notes           TEXT,
    status          VARCHAR(20) NOT NULL DEFAULT 'open', -- open / closed
    created_at, updated_at
);

-- Tambah kolom ke orders.orders
ALTER TABLE orders.orders ADD COLUMN shift_id BIGINT REFERENCES authentication.shifts(id);
ALTER TABLE orders.orders ADD COLUMN user_id BIGINT REFERENCES authentication.users(id);
```

### Aturan
- **Timestamp (`opened_at`, `closed_at`) dikirim dari tablet**, server simpan apa adanya
- Server tidak pakai `now()` / timezone Laravel untuk kalkulasi waktu shift
- **Durasi shift sepenuhnya dari tablet** — `duration = closed_at - opened_at` (keduanya dari tablet, jadwal konsisten)
- **1 user hanya boleh punya 1 shift active** dalam satu waktu

### API Endpoints

| Method | Endpoint | Auth | Fungsi |
|---|---|---|---|
| `POST` | `/api/shifts/store` | EnsureApiToken | Buka shift baru |
| `PUT` | `/api/shifts/close` | EnsureApiToken | Tutup shift |
| `GET` | `/api/shifts/active` | EnsureApiToken | Cek shift active user ini |
| `GET` | `/api/shifts/{guid}` | EnsureApiToken | Detail shift (summary) |
| `POST` | `/api/shifts` | EnsureApiToken | List shift (filter) |

### 1. Buka Shift — `POST /api/shifts/store`

**Request:**
```json
{
    "opened_at": "2026-06-03T08:00:00+07:00",
    "opening_balance": 500000,
    "notes": "Shift pagi"
}
```

**Validation:**
| Field | Rule |
|---|---|
| `opened_at` | required, date (ISO 8601 from tablet) |
| `opening_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

**Logic:**
1. Cek apakah user sudah punya shift `open` → jika ada: reject (`code: '03'`, message: "You already have an active shift.")
2. Generate `shift_number`: `SH-{Ymd}-{NNN}` (auto increment per hari)
3. Simpan `opened_at` dari request (server tidak mengubah)
4. `expected_balance` = `opening_balance` (belum ada transaksi)
5. Status = `open`

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

### 2. Tutup Shift — `PUT /api/shifts/close`

**Request:**
```json
{
    "guid": "uuid-shift",
    "closed_at": "2026-06-03T16:00:00+07:00",
    "closing_balance": 2500000,
    "notes": "Shift selesai lancar"
}
```

**Validation:**
| Field | Rule |
|---|---|
| `guid` | required, string, exists:shifts |
| `closed_at` | required, date (ISO 8601 from tablet) |
| `closing_balance` | required, numeric, min:0 |
| `notes` | nullable, string |

**Logic:**
1. Cari shift berdasarkan `guid` — validasi milik user ini & status `open`
2. Hitung `expected_balance` = `opening_balance` + SUM payment method `cash` status `paid` selama shift (from `orders.orders` → `orders.payments`)
3. Hitung `difference` = `closing_balance` - `expected_balance`
4. Simpan `closed_at` dari request (server tidak mengubah)
5. Status = `closed`
6. Kembalikan summary shift

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

### 3. Cek Shift Active — `GET /api/shifts/active`

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

### 4. Detail Shift — `GET /api/shifts/{guid}`

**Logic:**
1. Load shift + user info
2. Hitung summary: total_sales, cash_sales, digital_sales, order_count
3. Jika status `closed`: tampilkan closing_balance, expected_balance, difference

**Response (200):** Full data shift + summary + daftar orders (terbaru, max 50).

### 5. List Shift — `POST /api/shifts`

**Request:**
```json
{
    "limit": 20,
    "page": 1,
    "status": "closed",
    "user_guid": "uuid-user",
    "from_date": "2026-06-01",
    "to_date": "2026-06-03",
    "order": "opened_at",
    "sort": "DESC"
}
```

**Validation:**
| Field | Rule |
|---|---|
| `limit` | nullable, integer, min:1, max:100 |
| `page` | nullable, integer, min:1 |
| `status` | nullable, string, in:open,closed |
| `user_guid` | nullable, string, exists:authentication.users |
| `from_date` | nullable, date |
| `to_date` | nullable, date |
| `order` | nullable, string, in:shift_number,opened_at,closed_at,created_at |
| `sort` | nullable, string, in:ASC,DESC |

### Integrasi dengan Order (Tablet)

**`POST /api/orders/store`** — tambah field `shift_guid` (opsional):
```json
{
    "shift_guid": "uuid-shift",
    "items": [...],
    "payments": [...]
}
```
- Ketika `shift_guid` dikirim, order akan terikat ke shift tersebut
- Berguna untuk kalkulasi `expected_balance` saat tutup shift
- Jika tidak dikirim, `shift_id` = null (order tanpa shift)

### Dashboard Web — Shift Monitoring

| Halaman | Route | Fungsi |
|---|---|---|
| Shift index | `GET /shifts` | Lihat semua shift (active + history) |
| Shift detail | `GET /shifts/{guid}` | Detail shift, daftar order, rekap payment |
| Dashboard | `GET /` | Card active shifts (jumlah kasir aktif, durasi, sales) |

### Yang sudah dibuat

| Komponen | File |
|---|---|
| Model `Shift` | `app/Models/Shift.php` |
| API controller | `app/Http/Controllers/ShiftApiController.php` |
| Web controller | `app/Http/Controllers/ShiftPageController.php` |
| Vue: Shift list | `resources/js/Pages/Shift/Index.vue` |
| Vue: Shift detail | `resources/js/Pages/Shift/Show.vue` |
| Web routes | `routes/web.php` → `/shifts`, `/shifts/{guid}` |
| API routes | `routes/api.php` → `/api/shifts/*` |
| Sidebar | `AppSidebar.vue` → link `/shifts` |
| Dashboard | `Home/Index.vue` → active shifts card |
| Seeder roles | `AuthenticationRoleSeeder` → +Owner, Manager, Cashier |
| Seeder users | `AuthenticationUserSeeder` → +5 user (semua role) |
| Seeder catalog | `CatalogSeeder` → +25 produk dengan harga |
| Seeder orders | `OrderSeeder` → +2 shift (10 order) + 1 active shift |

### User test seed

| Username | Password | Role |
|---|---|---|
| `xander@wit.id` | `wit.id` | Superadmin |
| `owner@wit.id` | `owner123` | Owner |
| `manager@wit.id` | `manager123` | Manager |
| `ahmad@wit.id` | `ahmad123` | Cashier |
| `dewi@wit.id` | `dewi123` | Cashier |

### Saran Index PostgreSQL
```sql
CREATE INDEX idx_shifts_user_guid ON authentication.shifts(user_guid);
CREATE INDEX idx_shifts_status ON authentication.shifts(status);
CREATE INDEX idx_shifts_opened_at ON authentication.shifts(opened_at);
CREATE INDEX idx_orders_shift_id ON orders.orders(shift_id);
CREATE INDEX idx_orders_user_id ON orders.orders(user_id);
```
