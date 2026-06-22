# Project Memory - POS SBY WIT (Boilerplate POS Backend)

## Stack
- Laravel 13, PHP 8.3, Inertia.js 3, Vue, PostgreSQL.
- Development environment: Laragon.
- Project has 2 interfaces sharing the same backend:
  - Dashboard Web via Inertia pages for owner/manager/admin monitoring.
  - Tablet POS App via API endpoints for cashier operations.

## Actual Database Schemas
- `public.users`: default Laravel users table. This table now has `guid_cabang` with default value `PUSAT` (`aaaaaaaa-aaaa-4000-8000-000000000001`).
- `authentication.*`: POS authentication domain.
  - `authentication.roles`
  - `authentication.users` — has `guid_cabang` FK → `authentication.cabang.guid`
  - `authentication.user_details`
  - `authentication.api_clients`
  - `authentication.api_tokens`
  - `authentication.authentications`
   - `authentication.cabang` — master cabang (PUSAT, CBG1, CBG2)
- `product.*`: catalog and inventory domain.
  - `product.categories`
  - `product.groups`
  - `product.products`
  - `product.inventories`
   - `product.inventory_history`
- `orders.*`: order, payment, shift, reservation domain.
  - `orders.orders`
  - `orders.order_items`
  - `orders.payments`
  - `orders.shifts`
  - `orders.tables` — master meja restoran
  - `orders.table_reservations`
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
- Cabang: `/cabang`, `/cabang/store`, `/cabang/{guid}`, `/cabang/update`, delete `/cabang/{guid}`
- Categories: `/categories`, `/categories/store`, `/categories/{guid}`, `/categories/update`, delete `/categories/{guid}`
- Groups: `/groups`, `/groups/store`, `/groups/{guid}`, `/groups/update`, delete `/groups/{guid}`
- Products: `/products`, `/products/store`, `/products/{guid}`, `/products/update`, delete `/products/{guid}`
- Inventory: `/inventory`, `/inventory/store`, `/inventory/{guid}`, `/inventory/update`, delete `/inventory/{guid}`, `/inventory/adjust`, `/inventory/history`
- Orders: `/orders`, `/orders/store`, `/orders/{guid}`, `/orders/update`, delete `/orders/{guid}`
- Payments: `/payments`, `/payments/store`, `/payments/{guid}`
- Tables: `/tables`, `/tables/store`, `/tables/{guid}`, `/tables/update`, delete `/tables/{guid}`, `/tables/status/all`
- Reservations: `/reservations`, `/reservations/store`, `/reservations/{guid}`, `/reservations/update`, delete `/reservations/{guid}`
- Shifts: `/shift/store`, `/shift/close`, `/shift/active`, `/shift/{guid}`, `/shift`

Web routes are in `routes/web.php`:
- `/` dashboard home
- `/cabang` branch management (CRUD via `/cabang/items/*`)
- `/catalog` product catalog management
- `/inventory` inventory stock management
- `/inventory/history` global riwayat mutasi stok (history at `/inventory/items/{guid}/history`)
- `/orders` web order page
- `/shifts` and `/shifts/{guid}` shift monitoring
- `/reservations` table reservation management
- `/reports`, `/reports/exports`, `/reports/{type}/preview`, `/reports/{type}/summary`, `/reports/{type}/export`, export status/download routes
- `/settings/profile`
- `/users` and `/users/items/*` user management
- `/roles` and `/roles/items/*` role management
- `/permissions` permission management per role

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
- Columns: `id`, `guid`, `sku` (unique), `category_guid`, `group_guid`, `name`, `description`, `image`, `price`, `guid_cabang`, `is_active`, timestamps
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
| `guid_cabang` | UUID | | FK → `authentication.cabang.guid` |
| `unit` | VARCHAR(20) | `pcs` | |
| `current_stock` | DECIMAL(15,2) | 0 | Diupdate real-time via InventoryService |
| `minimum_stock` | DECIMAL(15,2) | 0 | |
| `is_active` | BOOLEAN | true | |
| timestamps | | | |

Unique: `(product_guid, guid_cabang)`.

#### `product.inventory_history` — Riwayat Mutasi Stok

Mencatat setiap perubahan stok (in/out/adjustment) sebagai audit trail.

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | BIGINT | | PK |
| `guid` | UUID | | Unique |
| `inventory_id` | UUID | | FK → `product.inventories.guid` |
| `product_guid` | UUID | | Denormalisasi dari inventory |
| `guid_cabang` | UUID | | FK → `authentication.cabang.guid`, denormalisasi dari inventory |
| `type` | ENUM('in','out','adjustment') | | `in` = stok masuk, `out` = stok keluar, `adjustment` = penyesuaian |
| `qty` | DECIMAL(15,2) | | Selalu positif; arah ditentukan oleh `type` |
| `stock_before` | DECIMAL(15,2) | | Stok sebelum mutasi |
| `stock_after` | DECIMAL(15,2) | | Stok setelah mutasi |
| `reference_type` | VARCHAR(50) | nullable | `order`, `manual_adjustment` |
| `reference_id` | UUID | nullable | GUID referensi (order_guid, dll) |
| `notes` | TEXT | nullable | Keterangan tambahan |
| `is_active` | BOOLEAN | true | Soft delete flag |
| `created_by` | UUID | nullable | FK → `authentication.users.guid` |
| `user_guid_reff` | UUID | nullable | FK → `authentication.users.guid`, GUID user referensi (kasir untuk order, admin untuk manual) |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

Index: `inventory_id`, `product_guid`, `guid_cabang`, `reference_type`, `reference_id`, `is_active`, `created_at`, `user_guid_reff`.

### Rules

- `guid_cabang` is UUID FK → `authentication.cabang.guid`. Default cabang adalah PUSAT (`aaaaaaaa-aaaa-4000-8000-000000000001`).
- Current default branch is `PUSAT`.
- Current default unit for all seeded restaurant POS stock is `pcs`.
- `CatalogSeeder` creates one inventory row per product with `guid_cabang = PUSAT GUID`, `unit = pcs`, `current_stock = 0`, and `minimum_stock = 0`.
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
- `database/migrations/2026_06_06_000002_add_is_active_to_inventory_history.php` — add `is_active`
- `database/migrations/2026_06_06_000003_add_user_guid_reff_to_inventory_history.php` — add `user_guid_reff`
- `database/seeders/InventoryHistorySeeder.php` — seeder stok awal
- `app/Models/ProductInventory.php` — model `product.inventories`
- `app/Models/InventoryHistory.php` — model `product.inventory_history`
- `app/Http/Controllers/InventoryController.php` — API CRUD
- `app/Http/Controllers/InventoryPageController.php` — Web/Inertia UI
- `app/Http/Controllers/InventoryAdjustmentController.php` — API adjustment & history endpoint
- `app/Services/Inventory/InventoryService.php` — business logic
- `app/Services/Inventory/InsufficientStockException.php` — custom exception
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
| POST | `/inventory/adjust` | `InventoryAdjustmentController@adjust` | Adjust stock (in/out/adjustment) |
| POST | `/inventory/history` | `InventoryAdjustmentController@history` | List history of an inventory |

#### Web
| Method | URI | Controller@Method | Notes |
|---|---|---|---|
| GET | `/inventory` | `InventoryPageController@index` | Halaman inventory |
| POST | `/inventory/items` | `InventoryPageController@store` | Create inventory |
| PUT | `/inventory/items/{guid}` | `InventoryPageController@update` | Update (no stock) |
| DELETE | `/inventory/items/{guid}` | `InventoryPageController@destroy` | Delete |
| POST | `/inventory/items/adjust` | `InventoryPageController@adjust` | Adjust stock (in/out) |
| GET | `/inventory/history` | `InventoryPageController@historyIndex` | Riwayat global dengan filter |
| GET | `/inventory/items/{guid}/history` | `InventoryPageController@history` | Riwayat mutasi per item |

## Orders
Order tables:
- `orders.orders`
- `orders.order_items`
- `orders.payments`

Important fields:
- `orders.orders.status`: `draft`, `open`, `completed`, `cancelled`
- `orders.orders.payment_status`: `unpaid`, `partial`, `paid`, `refunded`
- `orders.orders.shift_id` and `orders.orders.user_id` exist from shift integration.

Stock mutation reference on order:
- Setiap order yang completed/cancelled memiliki `stock_mutations` di data response (dari `InventoryHistory` dengan `reference_type=order`).
- Menampilkan GUID mutasi dan jumlah item yang terpengaruh.

Order creation paths:
- Tablet API: `App\Http\Controllers\OrderController@store`
- Dashboard web: `App\Http\Controllers\OrderPageController@store`

Order completion path:
- Dashboard web: `App\Http\Controllers\OrderPageController@complete` — deducts stock via `InventoryService::adjustStock()`
- Dashboard web: `App\Http\Controllers\OrderPageController@cancel` — restores stock via `InventoryService::adjustStock()`
- Future API completion/status update must also call the same inventory deduction service.

Inventory rule:
- Deduct stock only when order status transitions to `completed`.
- Restore stock when order status transitions to `cancelled`.
- Do not deduct stock on `draft`, `open`, payment creation, or order creation.
- Idempotent: sudah ada history dengan `reference_type=order` + `reference_id=order_guid` = skip.

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
- Export supports two formats: `csv` (default, via `fputcsv`) and `xlsx` (via PhpSpreadsheet with auto-width columns).
- Format is accepted as `format` parameter (csv/xlsx) in the export request.
- `ExportReportJob` handles both formats via separate methods (`writeCsv`, `writeXlsx`).

## API Prefix
`bootstrap/app.php` currently sets `apiPrefix: ''`, so API routes are mounted without the `/api` prefix.

Examples:
- Use `/products`, not `/api/products`.
- Use `/orders`, not `/api/orders`.
- Use `/inventory`, not `/api/inventory`.

If a future change adds `apiPrefix: 'api'`, update API docs, AGENTS.md, and clients together.

## Product API Fields
- `Product` model includes `sku` (unique, nullable) and `guid_cabang` fields.
- `ProductController::productData()` returns `sku` and `guid_cabang` in response.
- `ProductController::rules()` validates `sku` (unique) and `guid_cabang` (exists in `Cabang`).
- Product list supports filtering by `set_sku`, `set_guid_cabang`, `set_is_active`.

## Cabang CRUD
- `Cabang` model, migration, seeder (PUSAT, CBG1, CBG2).
- `CabangController` — API CRUD with Filterable trait.
- `CabangPageController` — Web/Inertia CRUD.
- `Cabang/Index.vue` — Filter panel, pagination, modal CRUD.
- API: `/cabang`, `/cabang/store`, `/cabang/{guid}`, `/cabang/update`, `/cabang/{guid}` (DELETE).
- Web: `/cabang` (GET), `/cabang/items` (POST), `/cabang/items/{guid}` (PUT/DELETE).

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
- `CabangSeeder`
- `AuthenticationUserSeeder`
- `CatalogSeeder`
- `InventoryHistorySeeder`
- `OrderSeeder`
- `ShiftSeeder`
- `TableReservationSeeder`
- default Laravel `User::factory()` test user

Seeder branch defaults:
- Public users factory uses `guid_cabang = PUSAT GUID`.
- Catalog inventory seed uses `guid_cabang = PUSAT GUID`.

## Tables & Reservations
- `orders.tables` stores master table data with static status (available/maintenance).
- Real-time status via `RestaurantTable::resolveStatus()`:
  - If reservation `status = occupied` (walk-in from order) → `occupied`
  - If `open` order exists with matching `table_number` → `occupied` (legacy fallback)
  - If today has `pending`/`confirmed` reservation and current time <= `end_time` → `reserved`
  - If `maintenance` → `maintenance`
  - Otherwise → `available`
- `orders.table_reservations` has columns: `end_time` (TIME nullable), `type` (booking/walkin)
- Reservation statuses: `occupied` (walk-in from dine-in order), `pending`, `confirmed`, `seated`, `completed`, `cancelled`
- Time-range: bookings with `end_time` set only show as `reserved` until end_time passes. Without `end_time` (or walkin), reserved all day.
- Interactive table map on reservation page with real-time polling (10s), "Kosongkan" button for occupied, "Edit" button for reserved.
- Auto-create reservation (type=walkin, status=occupied) when dine-in order created with table_number.
- Auto-complete walkin reservation when order is completed or cancelled.
- Release endpoint: `/reservations/{guid}/release` (POST) — completes reservation + clears order table_number.
- Release order endpoint: `/orders/{guid}/release-table` (POST) — clears order table_number directly.
- Orders page: Table select only visible for `dine_in` order type; hidden for `takeaway`/`delivery`.
- Seeder: `takeaway`/`delivery` orders have `table_number = null`.

## Users & Roles CRUD

### API Controllers
- `App\Http\Controllers\RoleController` — API CRUD roles (index, store, show, update, destroy). Uses `Filterable` trait. Delete fails if role has users.
- `App\Http\Controllers\UserController` — API CRUD users. Search by username/name/email (ILIKE), filter by role_name/guid_cabang/is_active. Password hashing via SHA-256 + salt (base64).

### Web Controllers
- `App\Http\Controllers\RolePageController` — Web CRUD roles via Inertia. Routes at `/roles/items/*`.
- `App\Http\Controllers\UserPageController` — Web CRUD users via Inertia. Routes at `/users/items/*`.
- `App\Http\Controllers\PermissionPageController` — Web permission management. Routes at `/permissions` (index), `/permissions/role/{guid}` (update).

### Vue Pages
- `resources/js/Pages/Users/Index.vue` — User management with search, filter by role/status, modal CRUD, button to Permission & Role
- `resources/js/Pages/Roles/Index.vue` — Role management with modal CRUD, shows users_count, delete protection, button to Permission
- `resources/js/Pages/Permissions/Index.vue` — Permission management per role with grouped checkboxes

## Permission System

### Tables
- `authentication.permissions` — master permissions (name, display_name, group, type)
- `authentication.role_permissions` — pivot role ↔ permission

### Models
- `App\Models\Permission` — Model for `authentication.permissions`
- `AuthenticationRole::permissions()` — BelongsToMany relation to permissions
- `AuthenticationRole::hasPermission(name)` — check if role has a specific permission

### Middleware
- `App\Http\Middleware\EnsurePermission` — checks `role->hasPermission()`. Supports web (session) and API (token) auth.
- Registered as alias `permission` in `bootstrap/app.php`.
- Usage: `Route::middleware('permission:api.orders.store')` or `$this->middleware('permission:menu.reports')`.

### Seeder
- `database/seeders/PermissionSeeder.php` — seeds 69 permissions (11 web + 58 API) and assigns to roles:
  - `Superadmin`: all permissions
  - `Owner`: dashboard, reports, exports (web only)
  - `Manager`: all web menus + API read/operational
  - `Cashier`: API orders, payments, products, shift, tables, reservations (no web)
  - `Users`: none

### API Routes (under `EnsureApiToken`)
| Method | URI | Controller |
|---|---|---|
| POST | `/roles` | `RoleController@index` |
| POST | `/roles/store` | `RoleController@store` |
| GET | `/roles/{guid}` | `RoleController@show` |
| PUT | `/roles/update` | `RoleController@update` |
| DELETE | `/roles/{guid}` | `RoleController@destroy` |
| POST | `/users` | `UserController@index` |
| POST | `/users/store` | `UserController@store` |
| GET | `/users/{guid}` | `UserController@show` |
| PUT | `/users/update` | `UserController@update` |
| DELETE | `/users/{guid}` | `UserController@destroy` |

### Web Routes (under `EnsureWebAuthenticated`)
| Method | URI | Controller |
|---|---|---|
| GET | `/users` | `UserPageController@index` |
| POST | `/users/items` | `UserPageController@store` |
| PUT | `/users/items/{guid}` | `UserPageController@update` |
| DELETE | `/users/items/{guid}` | `UserPageController@destroy` |
| GET | `/roles` | `RolePageController@index` |
| POST | `/roles/items` | `RolePageController@store` |
| PUT | `/roles/items/{guid}` | `RolePageController@update` |
| DELETE | `/roles/items/{guid}` | `RolePageController@destroy` |

### Password Rules
- Hash: `base64_encode(hash('sha256', $password.$salt, true))`
- Salt: `base64_encode(random_bytes(16))`
- Create: password + confirm_password required
- Update: password + confirm_password optional (kosongkan jika tidak diubah)

## Report Export Columns

All report exports now include `Cabang` (`cb.kode`) column added via left join to `authentication.cabang`.

| Report | Additional Columns |
|---|---|
| Sales | `Cabang` |
| Payments | `Cabang` (via `o.guid_cabang`) |
| Products | `SKU`, `Cabang` |
| Financial | `Cabang` (grouped per period + cabang) |
| Customers | `Cabang` |
| Order Status | `Cabang` |
| Catalog | `SKU`, `Cabang` |

## AppSidebar Scroll Behavior
- Desktop: entire `nav.side-nav` scrolls vertically (`overflow-y: auto`, `scroll-behavior: smooth`). Scrollbar hidden (`scrollbar-width: none`, `::-webkit-scrollbar { display: none }`).
- Mobile (<720px): entire `nav.side-nav` scrolls horizontally (`overflow-x: auto`). All items visible (no `display: none`). Scrollbar hidden.

## Development Notes
- Prefer existing Laravel/Eloquent patterns in the repo.
- Keep controller logic thin when adding larger modules; put business logic in services.
- Use Query Builder/SQL aggregate for dashboards and summaries with potentially large data.
- Keep dashboard UI consistent with existing Inertia pages and global responsive CSS.
