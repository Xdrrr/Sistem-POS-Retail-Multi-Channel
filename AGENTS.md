# Project Memory - POS SBY WIT (Boilerplate POS Backend)

## Stack
- Laravel 13, PHP 8.3, Inertia.js 3, Vue, PostgreSQL.
- Development environment: Laragon.
- Project has 2 interfaces sharing the same backend:
  - Dashboard Web via Inertia pages for owner/manager/admin monitoring.
  - Tablet POS App via API endpoints for cashier operations.

## Actual Database Schemas
- `public.users`: default Laravel users table. This table now has `id_cabang` with default value `PUSAT`.
- `authentication.*`: POS authentication domain.
  - `authentication.roles`
  - `authentication.users`
  - `authentication.user_details`
  - `authentication.api_clients`
  - `authentication.api_tokens`
  - `authentication.authentications`
- `product.*`: catalog and inventory domain.
  - `product.categories`
  - `product.groups`
  - `product.products`
  - `product.inventories`
   - `product.inventory_history`
- `orders.*`: order, payment, shift domain.
  - `orders.orders`
  - `orders.order_items`
  - `orders.payments`
  - `orders.shifts`
- `reports.*` is not a schema. Report export metadata uses `report_exports` model/table as implemented.

## Roles
Fixed role list:
- `Superadmin`: full dashboard and API access.
- `Owner`: dashboard reports/KPI and API access.
- `Manager`: dashboard reports, shift, catalog and API access.
- `Cashier`: no dashboard web access, full tablet POS API access.
- `Users`: default/legacy fallback, no dashboard and no API access.

Dashboard authorization:
- Web/Inertia pages are for `Superadmin`, `Owner`, and `Manager`.
- `Cashier` must not access dashboard web pages.

API authorization:
- API endpoints use `EnsureApiToken`.
- All roles except `Users` can access API endpoints.

## Actual Routes
API routes are in `routes/api.php`. Because `bootstrap/app.php` sets `apiPrefix: ''`, these routes are mounted without `/api`:
- Token: `/token/auth`, `/token/refresh`
- Authentication: `/authentication/login`, `/authentication/user/register`
- Categories: `/categories`, `/categories/store`, `/categories/{guid}`, `/categories/update`, delete `/categories/{guid}`
- Groups: `/groups`, `/groups/store`, `/groups/{guid}`, `/groups/update`, delete `/groups/{guid}`
- Products: `/products`, `/products/store`, `/products/{guid}`, `/products/update`, delete `/products/{guid}`
- Inventory: `/inventory`, `/inventory/store`, `/inventory/{guid}`, `/inventory/update`, delete `/inventory/{guid}`, `/inventory/adjust`, `/inventory/history`
- Orders: `/orders`, `/orders/store`, `/orders/{guid}`, `/orders/update`, delete `/orders/{guid}`
- Payments: `/payments`, `/payments/store`, `/payments/{guid}`
- Shifts: `/shift/store`, `/shift/close`, `/shift/active`, `/shift/{guid}`, `/shift`

Web routes are in `routes/web.php`:
- `/` dashboard home
- `/catalog` product catalog management
- `/inventory` inventory stock management (history at `/inventory/items/{guid}/history`)
- `/orders` web order page
- `/shifts` and `/shifts/{guid}` shift monitoring
- `/reports`, `/reports/exports`, `/reports/{type}/preview`, `/reports/{type}/summary`, `/reports/{type}/export`, export status/download routes
- `/settings/profile`

## Frontend Responsive Rules
- Global dashboard responsive behavior lives in `resources/css/app.css`.
- New pages should use the existing wrapper pattern: `AppSidebar`, `AppNavbar`, `.dashboard-shell`, and `.content`.
- Do not lock new pages with `overflow: hidden`, `height: 100vh`, or `height: calc(100vh...)` without clear mobile override.
- Tables or dense lists must use horizontal scroll on mobile (`.table-wrap` or `.table-scroll` pattern).
- Modals must be scrollable on mobile.
- Local responsive CSS is allowed only for page-specific layout needs.

## Catalog
Catalog tables:
- `product.categories`
- `product.groups`
- `product.products`

Catalog image rule:
- Seeder images live in `storage/app/public/catalog/seed/{categories|groups|products}/`.
- DB path format is `catalog/seed/{folder}/{Str::slug(name)}.png`.
- When adding/changing `CatalogSeeder` items, also add/update the PNG using the matching slug to avoid broken images.

Product model:
- `App\Models\Product`
- Table: `product.products`
- Relations: `category`, `group`, `inventories`

## Inventory

Inventory is stored in the `product` schema, not a separate schema.

### Tables

#### `product.inventories` — Stok Saat Ini

Stok tersedia per produk per cabang. `current_stock` di-update real-time setiap ada mutasi stok.

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | BIGINT | | PK |
| `guid` | UUID | | Unique |
| `product_guid` | UUID | | FK → `product.products.guid`, cascade on delete |
| `id_cabang` | VARCHAR(50) | `PUSAT` | |
| `unit` | VARCHAR(20) | `pcs` | |
| `current_stock` | DECIMAL(15,2) | 0 | Diupdate real-time via InventoryService |
| `minimum_stock` | DECIMAL(15,2) | 0 | |
| `is_active` | BOOLEAN | true | |
| timestamps | | | |

Unique: `(product_guid, id_cabang)`.

#### `product.inventory_history` — Riwayat Mutasi Stok

Mencatat setiap perubahan stok (in/out/adjustment) sebagai audit trail.

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | BIGINT | | PK |
| `guid` | UUID | | Unique |
| `inventory_id` | UUID | | FK → `product.inventories.guid` |
| `product_guid` | UUID | | Denormalisasi dari inventory |
| `id_cabang` | VARCHAR(50) | | Denormalisasi dari inventory |
| `type` | ENUM('in','out','adjustment') | | `in` = stok masuk, `out` = stok keluar, `adjustment` = penyesuaian |
| `qty` | DECIMAL(15,2) | | Selalu positif; arah ditentukan oleh `type` |
| `stock_before` | DECIMAL(15,2) | | Stok sebelum mutasi |
| `stock_after` | DECIMAL(15,2) | | Stok setelah mutasi |
| `reference_type` | VARCHAR(50) | nullable | `order`, `manual_adjustment` |
| `reference_id` | UUID | nullable | GUID referensi (order_guid, dll) |
| `notes` | TEXT | nullable | Keterangan tambahan |
| `created_by` | UUID | nullable | FK → `authentication.users.guid` |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

Index: `inventory_id`, `product_guid`, `id_cabang`, `reference_type`, `reference_id`, `created_at`.

### Rules

- `id_cabang` comes from branch code convention on `public.users.id_cabang`.
- Current default branch is `PUSAT`.
- Current default unit for all seeded restaurant POS stock is `pcs`.
- `CatalogSeeder` creates one inventory row per product with `id_cabang = PUSAT`, `unit = pcs`, `current_stock = 0`, and `minimum_stock = 0`.
- Inventory stock must be reduced when an order becomes `completed`, not when the order is merely created.
- Stock deduction must be idempotent: completing the same order twice cannot reduce stock twice. Deduct hanya sekali via `InventoryService::adjustStock()`.
- Setiap perubahan `current_stock` WAJIB melalui `InventoryService::adjustStock()` yang mencatat history.
- `current_stock` di `product.inventories` tetap di-update real-time (bukan derived dari history).
- Cancelled orders must not deduct stock.

### Sumber Mutasi Stok

| Arah | Sumber | `reference_type` |
|---|---|---|
| `out` | Order completed | `order` |
| `out` | Manual adjustment (admin kurangi) | `manual_adjustment` |
| `in` | Manual adjustment (admin tambah) | `manual_adjustment` |

### Service Layer

- `app/Services/Inventory/InventoryService.php` — method `adjustStock(inventory, type, qty, reference_type, reference_id, notes, created_by)`:
  1. Validasi stok cukup untuk tipe `out`
  2. Hitung `stock_before` / `stock_after`
  3. Update `current_stock` di `product.inventories`
  4. Simpan record ke `inventory_history`
  5. Return history record

### Actual inventory files

- `database/migrations/2026_06_05_000001_create_product_inventory_table.php` — migration `product.inventories`
- `database/migrations/2026_06_06_000001_create_product_inventory_history_table.php` — migration `product.inventory_history`
- `database/seeders/InventoryHistorySeeder.php` — seeder stok awal
- `app/Models/ProductInventory.php` — model `product.inventories`
- `app/Models/InventoryHistory.php` — model `product.inventory_history`
- `app/Http/Controllers/InventoryController.php` — API CRUD
- `app/Http/Controllers/InventoryPageController.php` — Web/Inertia UI
- `app/Http/Controllers/InventoryAdjustmentController.php` — API adjustment endpoint
- `app/Services/Inventory/InventoryService.php` — business logic
- `resources/js/Pages/Inventory/Index.vue` — Web/Inertia UI
- `resources/js/Pages/Inventory/History.vue` — History page

### Actual inventory routes

#### API
| Method | URI | Controller@Method | Notes |
|---|---|---|---|
| POST | `/inventory` | `InventoryController@index` | List inventory |
| POST | `/inventory/store` | `InventoryController@store` | Create inventory |
| GET | `/inventory/{guid}` | `InventoryController@show` | Detail inventory |
| PUT | `/inventory/update` | `InventoryController@update` | Update inventory fields (no stock) |
| DELETE | `/inventory/{guid}` | `InventoryController@destroy` | Delete inventory |
| POST | `/inventory/adjust` | `InventoryAdjustmentController@adjust` | Adjust stock (in/out/adjustment) |
| POST | `/inventory/history` | `InventoryAdjustmentController@history` | List history of an inventory |

#### Web
| Method | URI | Controller@Method | Notes |
|---|---|---|---|
| GET | `/inventory` | `InventoryPageController@index` | Halaman inventory |
| POST | `/inventory/items` | `InventoryPageController@store` | Create inventory |
| PUT | `/inventory/items/{guid}` | `InventoryPageController@update` | Update (no stock) |
| DELETE | `/inventory/items/{guid}` | `InventoryPageController@destroy` | Delete |
| POST | `/inventory/items/{guid}/adjust` | `InventoryPageController@adjust` | Adjust stock |
| GET | `/inventory/items/{guid}/history` | `InventoryPageController@history` | Riwayat mutasi |

## Orders
Order tables:
- `orders.orders`
- `orders.order_items`
- `orders.payments`

Important fields:
- `orders.orders.status`: `draft`, `open`, `completed`, `cancelled`
- `orders.orders.payment_status`: `unpaid`, `partial`, `paid`, `refunded`
- `orders.orders.shift_id` and `orders.orders.user_id` exist from shift integration.

Order creation paths:
- Tablet API: `App\Http\Controllers\OrderController@store`
- Dashboard web: `App\Http\Controllers\OrderPageController@store`

Order completion path:
- Dashboard web currently uses `App\Http\Controllers\OrderPageController@complete`
- Future API completion/status update must also call the same inventory deduction service.

Inventory rule:
- Deduct stock only when order status transitions to `completed`.
- Do not deduct stock on `draft`, `open`, payment creation, or order creation.
- Cancelled orders must not deduct stock.

## Shifts
Shift is already implemented, not merely planned.

Actual table:
- `orders.shifts`

Actual files:
- `database/migrations/2026_06_04_000001_create_order_shifts_table.php`
- `app/Models/Shift.php`
- `app/Services/Shifts/ShiftService.php`
- `app/Services/Shifts/ShiftSalesSummary.php`
- `app/Http/Controllers/ShiftApiController.php`
- `app/Http/Controllers/ShiftPageController.php`
- `resources/js/Pages/Shift/Index.vue`
- `resources/js/Pages/Shift/Show.vue`

Shift behavior:
- Cashier opens/closes shift from Tablet POS API.
- One user can only have one `open` shift.
- `orders.orders.shift_id` links order to shift.
- `orders.orders.user_id` links order to cashier.
- Shift summary is calculated from orders/payments linked by `shift_id`.

## Reports
Report page and API are implemented.

Actual files:
- `app/Http/Controllers/ReportPageController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Services/Reports/*ReportQuery.php`
- `app/Jobs/ExportReportJob.php`
- `app/Models/ReportExport.php`
- `resources/js/Pages/Reports/Index.vue`
- `resources/js/Pages/Reports/Exports.vue`

Report query rules:
- Controllers validate request and delegate query logic to services.
- Large report queries should use SQL aggregates, not collection `get()->sum()`.
- Export should stream/chunk data instead of loading everything into memory.
- Whitelist sort columns. Do not pass raw request order columns to `orderBy`.

## API Prefix
`bootstrap/app.php` currently sets `apiPrefix: ''`, so API routes are mounted without the `/api` prefix.

Examples:
- Use `/products`, not `/api/products`.
- Use `/orders`, not `/api/orders`.
- Use `/inventory`, not `/api/inventory`.

If a future change adds `apiPrefix: 'api'`, update API docs, AGENTS.md, and clients together.

## API Filter Pattern
List endpoints use the `set_` filter convention:

```json
{
  "filter": {
    "set_guid": true,
    "guid": "uuid",
    "set_status": false,
    "status": "active"
  },
  "limit": 20,
  "page": 1,
  "order": "name",
  "sort": "ASC"
}
```

Rules:
- `set_{field} = true` means the filter is active.
- `set_{field} = false` means ignore that value.
- Simple CRUD/list controllers may use `app/Traits/Filterable.php`.
- Complex reports should use dedicated query services.

## Seeder Order
`DatabaseSeeder` currently calls:
- `ApiClientSeeder`
- `AuthenticationRoleSeeder`
- `AuthenticationUserSeeder`
- `CatalogSeeder`
- `InventoryHistorySeeder`
- `OrderSeeder`
- `ShiftSeeder`
- default Laravel `User::factory()` test user

Seeder branch defaults:
- Public users factory uses `id_cabang = PUSAT`.
- Catalog inventory seed uses `id_cabang = PUSAT`.

## Development Notes
- Prefer existing Laravel/Eloquent patterns in the repo.
- Keep controller logic thin when adding larger modules; put business logic in services.
- Use Query Builder/SQL aggregate for dashboards and summaries with potentially large data.
- Keep dashboard UI consistent with existing Inertia pages and global responsive CSS.
