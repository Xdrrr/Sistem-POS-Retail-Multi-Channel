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

---

## Database Schema

### Schema: `authentication`
```
shifts
  ├── id, guid (uuid)
  ├── user_guid → users.guid
  ├── shift_number (SH-20260603-001)
  ├── opened_at (dari tablet), closed_at (dari tablet)
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

### Schema: `orders`
```
orders
  ├── id, guid (uuid)
  ├── order_number (unique)
  ├── shift_id → shifts.id (akan ditambahkan)
  ├── user_id → users.id (akan ditambahkan)
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

## Shift Feature (Akan Dibangun)

### Flow Shift Tablet
```
[Buka Shift]
  1. Tablet kirim: POST /api/shifts/store
     { opened_at, opening_balance, notes }
  2. Server validasi user belum punya shift active
  3. Generate shift_number: SH-{Ymd}-{NNN}
  4. Simpan opened_at dari tablet (tanpa perubahan)
  5. Status = open

[Transaksi Selama Shift]
  1. Setiap order menyertakan shift_guid
  2. Kalkulasi expected_balance saat tutup nanti

[Tutup Shift]
  1. Tablet kirim: PUT /api/shifts/close
     { guid, closed_at, closing_balance, notes }
  2. expected_balance = opening + SUM(cash_sales)
  3. difference = closing - expected
  4. Status = closed
  5. Kembalikan summary
```

### Endpoint Shift API

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/shifts/store` | Buka shift baru |
| `PUT` | `/api/shifts/close` | Tutup shift |
| `GET` | `/api/shifts/active` | Cek shift active user ini |
| `GET` | `/api/shifts/{guid}` | Detail shift + summary |
| `POST` | `/api/shifts` | List shift (filter) |

### Dashboard Shift Monitoring (Akan Dibangun)

| Halaman | Route | Fungsi |
|---|---|---|
| Shift index | `GET /shifts` | Lihat semua shift (active + history) |
| Shift detail | `GET /shifts/{guid}` | Detail shift, daftar order, rekap payment |
| Dashboard | `GET /` | Card active shifts (jumlah kasir aktif, durasi, sales) |

---

## Report (Akan Dibangun)

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
Implementasi: `app/Traits/Filterable.php`

### Export Architecture Plan
- **Library:** OpenSpout (`openspout/openspout`) — streaming writer, memory fixed
- **Queue:** Process via Job biar user ga nunggu
- **File:** Simpan sementara di `storage/app/reports/`, cleanup scheduler
- **Chunk:** `chunkById(500)` — jangan `->get()` atau `->all()`

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
