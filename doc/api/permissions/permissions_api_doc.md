# Permissions System

## Overview

Setiap role memiliki daftar permission yang mengontrol akses ke menu web dan endpoint API. Permission di-assign melalui halaman `/permissions` di dashboard.

## Database

### `authentication.permissions`

| Column | Type | Description |
|---|---|---|
| `guid` | UUID | Unique identifier |
| `name` | VARCHAR(100) | Identifier permission, misal `api.orders.store` |
| `display_name` | VARCHAR(200) | Nama tampilan, misal "Buat Order" |
| `group` | VARCHAR(50) | Grup, misal "orders", "products" |
| `type` | VARCHAR(20) | `web` (menu sidebar) atau `api` (endpoint) |

### `authentication.role_permissions`

Many-to-many: satu role punya banyak permission, satu permission bisa dipakai banyak role.

| Column | Type | Description |
|---|---|---|
| `role_id` | BIGINT FK | `authentication.roles.id` |
| `permission_guid` | UUID FK | `authentication.permissions.guid` |
| | UNIQUE | `(role_id, permission_guid)` |

## Daftar Permission API

### Category: `orders`
| Name | Display Name |
|---|---|
| `api.orders.index` | List Order |
| `api.orders.store` | Buat Order |
| `api.orders.show` | Detail Order |
| `api.orders.update` | Update Order |
| `api.orders.destroy` | Hapus Order |

### Category: `payments`
| Name | Display Name |
|---|---|
| `api.payments.index` | List Pembayaran |
| `api.payments.store` | Buat Pembayaran |
| `api.payments.show` | Detail Pembayaran |

### Category: `products`
| Name | Display Name |
|---|---|
| `api.products.index` | List Produk |
| `api.products.store` | Tambah Produk |
| `api.products.show` | Detail Produk |
| `api.products.update` | Update Produk |
| `api.products.destroy` | Hapus Produk |

### Category: `categories`
| Name | Display Name |
|---|---|
| `api.categories.index` | List Kategori |
| `api.categories.store` | Tambah Kategori |
| `api.categories.show` | Detail Kategori |
| `api.categories.update` | Update Kategori |
| `api.categories.destroy` | Hapus Kategori |

### Category: `groups`
| Name | Display Name |
|---|---|
| `api.groups.index` | List Grup |
| `api.groups.store` | Tambah Grup |
| `api.groups.show` | Detail Grup |
| `api.groups.update` | Update Grup |
| `api.groups.destroy` | Hapus Grup |

### Category: `inventory`
| Name | Display Name |
|---|---|
| `api.inventory.index` | List Inventory |
| `api.inventory.store` | Tambah Inventory |
| `api.inventory.show` | Detail Inventory |
| `api.inventory.update` | Update Inventory |
| `api.inventory.destroy` | Hapus Inventory |
| `api.inventory.adjust` | Adjust Stok |
| `api.inventory.history` | Riwayat Stok |

### Category: `cabang`
| Name | Display Name |
|---|---|
| `api.cabang.index` | List Cabang |
| `api.cabang.store` | Tambah Cabang |
| `api.cabang.show` | Detail Cabang |
| `api.cabang.update` | Update Cabang |
| `api.cabang.destroy` | Hapus Cabang |

### Category: `tables`
| Name | Display Name |
|---|---|
| `api.tables.index` | List Meja |
| `api.tables.store` | Tambah Meja |
| `api.tables.show` | Detail Meja |
| `api.tables.update` | Update Meja |
| `api.tables.destroy` | Hapus Meja |
| `api.tables.statusAll` | Status Semua Meja |

### Category: `reservations`
| Name | Display Name |
|---|---|
| `api.reservations.index` | List Reservasi |
| `api.reservations.store` | Tambah Reservasi |
| `api.reservations.show` | Detail Reservasi |
| `api.reservations.update` | Update Reservasi |
| `api.reservations.destroy` | Hapus Reservasi |

### Category: `shift`
| Name | Display Name |
|---|---|
| `api.shift.store` | Buka Shift |
| `api.shift.close` | Tutup Shift |
| `api.shift.active` | Shift Aktif |
| `api.shift.show` | Detail Shift |
| `api.shift.index` | List Shift |

### Category: `roles`
| Name | Display Name |
|---|---|
| `api.roles.index` | List Role |
| `api.roles.store` | Tambah Role |
| `api.roles.show` | Detail Role |
| `api.roles.update` | Update Role |
| `api.roles.destroy` | Hapus Role |

### Category: `users`
| Name | Display Name |
|---|---|
| `api.users.index` | List User |
| `api.users.store` | Tambah User |
| `api.users.show` | Detail User |
| `api.users.update` | Update User |
| `api.users.destroy` | Hapus User |

## Daftar Permission Web Menu

| Name | Display Name |
|---|---|
| `menu.dashboard` | Menu Dashboard |
| `menu.reservation` | Menu Reservasi |
| `menu.tables` | Menu Meja |
| `menu.shift` | Menu Shift |
| `menu.reports` | Menu Laporan |
| `menu.exports` | Menu History Export |
| `menu.catalog` | Menu Katalog |
| `menu.inventory` | Menu Inventory |
| `menu.cabang` | Menu Cabang |
| `menu.users` | Menu Users |
| `menu.roles` | Menu Roles |

## Default Assignment per Role

| Role | Web Menu | API |
|---|---|---|
| **Superadmin** | Semua | Semua |
| **Owner** | dashboard, reports, exports | — |
| **Manager** | Semua menu | read-only + operasional |
| **Cashier** | — | orders, payments, products, shift, tables, reservations |
| **Users** | — | — |

## Middleware

Gunakan middleware `permission:{name}` di route:

```php
Route::middleware([EnsureApiToken::class, 'permission:api.orders.store'])->group(function () {
    Route::post('/orders/store', [OrderController::class, 'store']);
});
```

Atau di controller:

```php
$this->middleware('permission:api.orders.store')->only('store');
```

### Cek permission secara manual:

```php
$user->role->hasPermission('api.orders.store'); // return bool
```
