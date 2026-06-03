# Project Memory — POS SBY WIT (Boilerplate POS Backend)

## Stack
- Laravel 13 + PHP 8.3 + Inertia.js 3 + PostgreSQL

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

## What's Missing for Reports
- No report controllers or routes
- No export features (Excel/PDF/CSV)
- No user_id on orders table (can't do cashier performance report)
- Report sidebar links are placeholders (`href: '#'`)
