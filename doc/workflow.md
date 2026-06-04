# Workflow Project — POS SBY WIT (Boilerplate POS Backend)

## Arsitektur Aplikasi

Project memiliki 2 interface yang sharing 1 backend:

| Interface | Target User | Akses | Teknologi |
|---|---|---|---|
| **Dashboard Web** | Owner/Manager/Admin | Browser via Inertia.js | Laravel + Vue 3 + Inertia |
| **Tablet POS** | Kasir/Karyawan | Mobile app (terpisah) | REST API |

```
┌─────────────────────────────────────────────────────────┐
│                    Laravel Backend                        │
│                                                          │
│  ┌─────────────────┐       ┌────────────────────────┐   │
│  │  Web Routes      │       │  API Routes            │   │
│  │  (Inertia SSR)   │       │  (JSON Response)       │   │
│  └────────┬────────┘       └────────┬───────────────┘   │
│           │                         │                    │
│  ┌────────┴─────────────────────────┴───────────────┐   │
│  │            PostgreSQL Database                    │   │
│  │  ┌────────────┐  ┌──────────┐  ┌───────────┐    │   │
│  │  │authentication│ │product   │  │orders     │    │   │
│  │  │ - roles     │  │ - categories│ - orders  │    │   │
│  │  │ - users     │  │ - groups │  │ - order_items │  │   │
│  │  │ - user_details│ │ - products│ - payments │   │   │
│  │  └────────────┘  └──────────┘  └───────────┘    │   │
│  └────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

---

## Role User (Fixed List)

| Role | Target User | Akses Dashboard | Akses API |
|---|---|---|---|
| **Superadmin** | Developer/IT | ✅ Full | ✅ Full |
| **Owner** | Pemilik usaha | ✅ Semua laporan & KPI | ✅ |
| **Manager** | Pengelola operasional | ✅ Laporan, shift, katalog | ✅ |
| **Cashier** | Kasir/karyawan | ❌ Tidak | ✅ Full (tablet POS) |
| **Users** | (default/legacy) | ❌ | ❌ |

Registrasi baru selalu fallback ke role `Users` via `AuthPageController` & `AuthenticationController`.

---

## Alur Dashboard Web (Inertia)

```
[Browser] ←→ [Inertia.js] ←→ [Laravel Controller] ←→ [PostgreSQL]
```

### Frontend Responsive System

Responsive utama dashboard web dikelola secara global di `resources/css/app.css`.

Prinsip:
- Global responsive mengatur scroll mobile/tablet untuk `html`, `body`, `#app`, `.dashboard-shell`, dan `.content`.
- Page baru harus memakai pola wrapper `AppSidebar`, `AppNavbar`, `.dashboard-shell`, dan `.content` agar otomatis mengikuti responsive global.
- Jangan mengunci page dengan `overflow: hidden`, `height: 100vh`, atau `height: calc(100vh...)` tanpa override mobile yang jelas.
- Table atau daftar dengan banyak kolom harus memakai horizontal scroll (`.table-wrap` / `.table-scroll`) pada mobile.
- Modal harus bisa discroll pada mobile; jangan membuat modal fixed-height tanpa overflow yang bisa digulir.
- Override responsive lokal di file Vue hanya untuk kebutuhan spesifik page/komponen. Aturan scroll, grid satu kolom, table overflow, dan modal scroll tetap bersumber dari global CSS.

Target viewport:
- HP: konten scroll secara normal, sidebar menjadi bottom navigation, grid menjadi satu kolom.
- Tablet: layout tetap terbaca, konten tidak terkunci di tinggi viewport, tabel bisa horizontal scroll.
- Desktop/PC: layout sidebar kiri + top navbar tetap memakai ruang kerja penuh.

### Flow Login
```
GET /login          → AuthPageController@login()     → render Auth/Login.vue
POST /login         → AuthPageController@authenticate() → validate email+password
                        └→ hash_equals(password, stored)
                        └→ session → web_auth_user_id
                        └→ redirect → /
POST /logout        → AuthPageController@logout()    → clear session → redirect /login
```

### Middleware Chain
```
RedirectIfWebAuthenticated (login/register pages only)
└→ jika sudah login → redirect ke dashboard

EnsureWebAuthenticated (all protected pages)
└→ jika session tidak ada web_auth_user_id → redirect ke login

HandleInertiaRequests (global)
└→ share auth user + role ke Vue frontend
```

### Route Web

| Method | URI | Controller@Method | Keterangan |
|---|---|---|---|
| GET | `/` | `HomePageController@index` | Dashboard utama, KPIs |
| GET | `/catalog` | `CatalogPageController@index` | Manajemen produk |
| POST | `/catalog/*` | `CatalogPageController@*` | CRUD kategori, grup, produk |
| GET | `/orders` | `OrderPageController@index` | Lihat order |
| POST | `/orders/create` | `OrderPageController@store` | Buat order baru |
| PUT | `/orders/{guid}/complete` | `OrderPageController@complete` | Selesaikan order |
| PUT | `/orders/{guid}/cancel` | `OrderPageController@cancel` | Batalkan order |
| GET | `/shifts` | `ShiftPageController@index` | Monitoring shift |
| GET | `/shifts/{guid}` | `ShiftPageController@show` | Detail shift dan summary sales |
| GET | `/reports` | `ReportPageController@index` | Halaman laporan |
| POST | `/reports/{type}/preview` | `ReportController@preview` | Preview/list data report |
| POST | `/reports/{type}/summary` | `ReportController@summary` | Agregat/KPI report |
| POST | `/reports/{type}/export` | `ReportController@export` | Buat job export report |
| GET | `/reports/exports/{guid}` | `ReportController@exportStatus` | Cek status export |
| GET | `/reports/exports/{guid}/download` | `ReportController@download` | Download hasil export |
| GET | `/settings/profile` | `ProfilePageController@edit` | Edit profil |
| PUT | `/settings/profile` | `ProfilePageController@update` | Update profil |

### Dashboard KPI (HomePageController)
```
Harian (startOfDay → endOfDay):
  sales_total       = SUM total_amount (completed, non-cancelled)
  cash_total        = SUM payment cash
  digital_total     = SUM payment non-cash
  transactions_today= COUNT orders
  active_shift      = HH:MM:SS (sementara: hitung dari tengah malam)
  pending_payments  = COUNT unpaid/partial
  completed_orders  = COUNT status=completed
  hourly_sales      = breakdown per jam 08:00-15:00
  recent_orders     = 5 order terbaru
```

---

## Alur API Tablet

```
[Tablet App] ←→ [HTTP JSON] ←→ [Laravel Controller] ←→ [PostgreSQL]
```

### Auth API Token Flow
```
POST /api/token/auth         → TokenAuthController@auth
POST /api/token/refresh      → TokenAuthController@refresh
POST /api/authentication/login   → AuthenticationController@login
POST /api/authentication/user/register → AuthenticationController@register
```

Setiap request API wajib header: `Authorization: Bearer {token}`

### Route API

| Method | URI | Controller@Method |
|---|---|---|
| POST | `/api/categories` | `CategoryController@index` |
| POST | `/api/categories/store` | `CategoryController@store` |
| GET | `/api/categories/{guid}` | `CategoryController@show` |
| PUT | `/api/categories/update` | `CategoryController@update` |
| DELETE | `/api/categories/{guid}` | `CategoryController@destroy` |
| POST | `/api/groups` | `ProductGroupController@index` |
| POST | `/api/groups/store` | `ProductGroupController@store` |
| GET | `/api/groups/{guid}` | `ProductGroupController@show` |
| PUT | `/api/groups/update` | `ProductGroupController@update` |
| DELETE | `/api/groups/{guid}` | `ProductGroupController@destroy` |
| POST | `/api/products` | `ProductController@index` |
| POST | `/api/products/store` | `ProductController@store` |
| GET | `/api/products/{guid}` | `ProductController@show` |
| PUT | `/api/products/update` | `ProductController@update` |
| DELETE | `/api/products/{guid}` | `ProductController@destroy` |
| POST | `/api/orders` | `OrderController@index` |
| POST | `/api/orders/store` | `OrderController@store` |
| GET | `/api/orders/{guid}` | `OrderController@show` |
| PUT | `/api/orders/update` | `OrderController@update` |
| DELETE | `/api/orders/{guid}` | `OrderController@destroy` |
| POST | `/api/payments` | `PaymentController@index` |
| POST | `/api/payments/store` | `PaymentController@store` |
| GET | `/api/payments/{guid}` | `PaymentController@show` |
| POST | `/shift/store` | `ShiftApiController@store` |
| PUT | `/shift/close` | `ShiftApiController@close` |
| GET | `/shift/active` | `ShiftApiController@active` |
| GET | `/shift/{guid}` | `ShiftApiController@show` |
| POST | `/shift` | `ShiftApiController@index` |

### Flow Order Store (Tablet)
```
POST /api/orders/store
  ├── shift_guid (opsional, jika ada → link ke shift)
  ├── items[] (required)
  │   ├── product_guid
  │   ├── quantity
  │   ├── unit_price (opsional, default dari product)
  │   └── discount_amount
  ├── payments[] (opsional)
  │   ├── method (cash/debit_card/credit_card/qris/transfer/e_wallet)
  │   ├── amount
  │   ├── paid_at
  │   └── reference_number
  ├── order_type (dine_in/takeaway/delivery)
  ├── ordered_at (dari tablet, server simpan apa adanya)
  └── ...
```

Catatan integrasi shift:
- Jika `shift_guid` dikirim, order harus terhubung ke shift milik user login yang statusnya `open`.
- Backend menyimpan `orders.shift_id = shifts.id` dan `orders.user_id = authenticated user id`.
- Relasi code wajib dua arah: `Shift hasMany Order`, `Order belongsTo Shift`.

---

## Database Schema

### Schema: `authentication`
```
shifts
  ├── id, guid (uuid)
  ├── user_id → users.id
  ├── user_guid → users.guid
  ├── shift_number (SH-20260603-001)
  ├── opened_at (dari tablet), closed_at (dari tablet)
  ├── work_hours (dari frontend/tablet)
  ├── opening_balance, closing_balance
  ├── expected_balance, difference
  ├── notes, status (open/closed)
  └── timestamps
```
```
roles
  ├── id, guid (uuid)
  ├── name (Superadmin, Owner, Manager, Cashier, Users)
  └── is_default (Users = true)

users
  ├── id, guid (uuid)
  ├── role_id → roles.id
  ├── username, password, salt (SHA-256 custom)
  ├── is_active, last_login
  └── url_image, fcm_token

user_details
  ├── id
  ├── user_id → users.id (unique)
  ├── full_name, email, phone_number
  ├── gender, address, city, province, date_of_birth
  └── additional_address (json)
```

### Schema: `product`
```
categories
  ├── id, guid (uuid)
  ├── name, description
  └── is_active

groups
  ├── id, guid (uuid)
  ├── name, description
  └── is_active

products
  ├── id, guid (uuid)
  ├── category_guid → categories.guid
  ├── group_guid → groups.guid
  ├── name, description
  ├── price (decimal 15,2)
  └── is_active
```

Catatan seed image katalog:
- `CatalogSeeder` harus mengisi field `image` untuk category, group, dan product.
- Dummy image seed disimpan di `storage/app/public/catalog/seed/{categories|groups|products}/`.
- Path image mengikuti nama data: `catalog/seed/{folder}/{Str::slug(name)}.png`.
- Jika data seeder ditambah atau nama item berubah, file PNG seed dengan slug yang sama wajib ikut ditambahkan/diupdate.

### Schema: `orders`
```
orders
  ├── id, guid (uuid)
  ├── order_number (unique)
  ├── shift_id → authentication.shifts.id (nullable)
  ├── user_id → authentication.users.id (nullable)
  ├── customer_name, customer_phone, table_number
  ├── order_type (dine_in/takeaway/delivery)
  ├── status (draft/open/completed/cancelled)
  ├── payment_status (unpaid/partial/paid/refunded)
  ├── subtotal, discount_amount, tax_amount, total_amount
  ├── ordered_at (dari tablet)
  └── notes

order_items
  ├── id, guid (uuid)
  ├── order_guid → orders.guid
  ├── product_guid → products.guid
  ├── product_name (snapshot)
  ├── quantity, unit_price, discount_amount, subtotal
  └── notes

payments
  ├── id, guid (uuid)
  ├── payment_number (unique)
  ├── order_guid → orders.guid
  ├── method (cash/debit_card/credit_card/qris/transfer/e_wallet)
  ├── status (pending/paid/failed/refunded)
  ├── amount (decimal 15,2)
  ├── paid_at (dari tablet)
  ├── reference_number
  └── notes
```

---

## Shift Feature Plan (Build From Scratch)

Fitur shift dianggap belum ada di project. Implementasi dimulai dari migration, model relation, service, API controller, integrasi order, lalu dashboard monitoring.

### Struktur Code yang Disarankan
- `app/Models/Shift.php`
- `app/Services/Shifts/ShiftService.php`
- `app/Services/Shifts/ShiftSalesSummary.php`
- `app/Http/Controllers/ShiftApiController.php`
- `app/Http/Controllers/ShiftPageController.php`
- `resources/js/Pages/Shift/Index.vue`
- `resources/js/Pages/Shift/Show.vue`

### Workflow Ringan
```
[Open Shift]
  1. Tablet kirim POST /shift/store
     { opened_at, work_hours, opening_balance, notes }
  2. Backend validasi cashier belum punya shift open.
  3. Backend generate shift_number: SH-{Ymd}-{NNN}.
  4. Backend simpan opened_at dan work_hours dari frontend.
  5. expected_balance = opening_balance.
  6. status = open.

[Order Selama Shift]
  1. Tablet mengirim shift_guid saat POST /api/orders/store.
  2. Backend validasi shift milik user login dan status open.
  3. Backend menyimpan orders.shift_id dan orders.user_id.
  4. Relasi code: Shift hasMany Order, Order belongsTo Shift.

[Close Shift]
  1. Tablet kirim PUT /shift/close
     { guid, closed_at, work_hours, closing_balance, notes }
  2. Backend simpan closed_at dan work_hours dari frontend.
  3. Backend hitung sales dari order dengan shift_id terkait.
  4. expected_balance = opening_balance + cash_sales.
  5. difference = closing_balance - expected_balance.
  6. status = closed.
```

### Request Open Shift
```json
{
  "opened_at": "2026-06-03T08:00:00+07:00",
  "work_hours": 8,
  "opening_balance": 500000,
  "notes": "Shift pagi"
}
```

### Request Close Shift
```json
{
  "guid": "uuid-shift",
  "closed_at": "2026-06-03T16:00:00+07:00",
  "work_hours": 8,
  "closing_balance": 2500000,
  "notes": "Shift selesai"
}
```

### Summary Sales Shift
Summary dihitung berdasarkan `orders.orders.shift_id`, bukan hanya range tanggal.

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
- `total_sales`: SUM `orders.total_amount` untuk order completed.
- `cash_sales`: SUM payment amount method `cash` status `paid`.
- `digital_sales`: SUM payment amount method selain `cash` status `paid`.
- `order_count`: COUNT distinct order pada shift.
- Pakai SQL aggregate + `COALESCE`, bukan collection sum.

### Endpoint Shift API

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/shift/store` | Buka shift baru |
| `PUT` | `/shift/close` | Tutup shift |
| `GET` | `/shift/active` | Cek shift aktif user login |
| `GET` | `/shift/{guid}` | Detail shift + summary sales |
| `POST` | `/shift` | List shift dengan filter |

Request list shift memakai pola filter standar `set_*`:

```json
{
  "filter": {
    "set_guid": false,
    "guid": "uuid-shift",
    "set_status": true,
    "status": "closed",
    "set_user_guid": false,
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

### Dashboard Shift Monitoring

| Halaman | Route | Fungsi |
|---|---|---|
| Shift index | `GET /shifts` | Active shift dan history shift |
| Shift detail | `GET /shifts/{guid}` | Detail cashier, work hours, summary sales, daftar order |
| Dashboard | `GET /` | Card active cashier, sales shift berjalan, shift melewati estimasi work hours |

---

## Report (Akan Dibangun)

### Report Module Design

Report dibuat sebagai module reusable untuk Dashboard Web dan endpoint JSON berbasis web route `/reports/*` tanpa prefix `/api`.

Struktur yang disarankan:
- `app/Http/Controllers/ReportPageController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Services/Reports/SalesReportQuery.php`
- `app/Services/Reports/PaymentReportQuery.php`
- `app/Services/Reports/ProductReportQuery.php`
- `app/Services/Reports/FinancialReportQuery.php`
- `app/Services/Reports/CustomerReportQuery.php`
- `app/Services/Reports/OrderStatusReportQuery.php`
- `app/Services/Reports/CatalogReportQuery.php`
- `app/Jobs/ExportReportJob.php`
- `app/Models/ReportExport.php` atau table metadata export sejenis

### Endpoint Report

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/reports` | Render halaman report Inertia |
| `POST` | `/reports/{type}/preview` | Preview data report dengan filter dan pagination |
| `POST` | `/reports/{type}/summary` | Summary/agregat report |
| `POST` | `/reports/{type}/export` | Dispatch job export XLSX/CSV |
| `GET` | `/reports/exports/{guid}` | Cek status export: queued/processing/done/failed |
| `GET` | `/reports/exports/{guid}/download` | Download file hasil export |

### Filter Pattern (Set-True)
```json
{
  "filter": {
    "set_status": true,
    "status": "completed",
    "set_order_type": false,
    "order_type": "dine_in"
  },
  "limit": 20,
  "page": 1,
  "order": "ordered_at",
  "sort": "DESC"
}
```

Catatan:
- `app/Traits/Filterable.php` tetap boleh dipakai untuk CRUD/list sederhana
- Untuk report besar, buat filter khusus di service report
- Report filter harus support date range, multi-select `whereIn`, text search PostgreSQL `ILIKE`, aggregate `HAVING`, dan whitelist order column

### Query Pattern

- Controller hanya validasi request dan memanggil service report
- Service report menyediakan `baseQuery()`, `applyFilters()`, `preview()`, `summary()`, dan `exportRows()`
- Preview/page view menggunakan `paginate()` atau limit kecil
- Export menggunakan Query Builder + `select()` eksplisit agar tidak hydrate model Eloquent
- Agregat/KPI dihitung langsung di SQL (`SUM`, `COUNT`, `GROUP BY`), bukan `->get()->sum()` di collection
- Sorting harus memakai whitelist map kolom, jangan langsung `orderBy($request->order)`

### Export Architecture Plan
- **Library:** OpenSpout (`openspout/openspout`) — streaming writer, memory fixed
- **Queue:** Process via Job biar user ga nunggu
- **File:** Simpan sementara di `storage/app/reports/`, cleanup scheduler
- **Chunk:** `lazyById(500)` / `chunkById(500)` — jangan `->get()` atau `->all()`
- **Preview vs Export:** preview pakai pagination kecil, export selalu async via job
- **Cache:** cache query agregat/summary, bukan raw data
- **Metadata:** simpan status export, filter JSON, file path, row count, error message, requested_by, expired_at

Contoh export query:
```php
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

Catatan join + chunk:
- Sales export: chunk/lazy by `orders.orders.id`
- Payment export: chunk/lazy by `orders.payments.id`
- Product export: chunk/lazy by `orders.order_items.id`
- Pada query join, selalu pakai alias kolom id yang jelas agar tidak bentrok

### Index Report PostgreSQL

```sql
CREATE INDEX idx_orders_ordered_at ON orders.orders(ordered_at);
CREATE INDEX idx_orders_status_ordered_at ON orders.orders(status, ordered_at);
CREATE INDEX idx_orders_payment_status_ordered_at ON orders.orders(payment_status, ordered_at);
CREATE INDEX idx_orders_order_type_ordered_at ON orders.orders(order_type, ordered_at);

CREATE INDEX idx_payments_paid_at ON orders.payments(paid_at);
CREATE INDEX idx_payments_status_paid_at ON orders.payments(status, paid_at);
CREATE INDEX idx_payments_method_paid_at ON orders.payments(method, paid_at);

CREATE INDEX idx_order_items_order_guid ON orders.order_items(order_guid);
CREATE INDEX idx_order_items_product_guid ON orders.order_items(product_guid);
CREATE INDEX idx_products_category_guid ON product.products(category_guid);
CREATE INDEX idx_products_group_guid ON product.products(group_guid);
```

Search index opsional jika data besar:
```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX idx_orders_customer_name_trgm ON orders.orders USING gin (customer_name gin_trgm_ops);
CREATE INDEX idx_orders_customer_phone_trgm ON orders.orders USING gin (customer_phone gin_trgm_ops);
CREATE INDEX idx_products_name_trgm ON product.products USING gin (name gin_trgm_ops);
```

---

## Status Fitur

| Fitur | Status | Catatan |
|---|---|---|
| Login Web | ✅ | Custom session, SHA-256 password |
| Register Web | ✅ | Fallback role "Users" |
| Manajemen Katalog | ✅ | CRUD produk/kategori/grup via web |
| Manajemen Order Web | ✅ | Buat order, bayar, selesai, cancel |
| Dashboard KPI | ✅ | Harian, aktif |
| Role & Roles | 🟡 | Role list sudah fixed (5 role), tapi otorisasi belum diimplementasi |
| Shift | ❌ | Akan dibangun (migration + API + UI) |
| Laporan/Report + Export | ❌ | Akan dibangun (OpenSpout + queue) |
| Shift Filter Dashboard | ❌ | Akan ditambahkan ke dashboard |
