<?php

namespace Database\Seeders;

use App\Models\AuthenticationRole;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    private array $permissions = [
        // Web Menu
        ['name' => 'menu.dashboard', 'display_name' => 'Menu Dashboard', 'group' => 'dashboard', 'type' => 'web'],
        ['name' => 'menu.reservation', 'display_name' => 'Menu Reservasi', 'group' => 'reservation', 'type' => 'web'],
        ['name' => 'menu.tables', 'display_name' => 'Menu Meja', 'group' => 'tables', 'type' => 'web'],
        ['name' => 'menu.shift', 'display_name' => 'Menu Shift', 'group' => 'shift', 'type' => 'web'],
        ['name' => 'menu.reports', 'display_name' => 'Menu Laporan', 'group' => 'reports', 'type' => 'web'],
        ['name' => 'menu.exports', 'display_name' => 'Menu History Export', 'group' => 'exports', 'type' => 'web'],
        ['name' => 'menu.catalog', 'display_name' => 'Menu Katalog', 'group' => 'catalog', 'type' => 'web'],
        ['name' => 'menu.inventory', 'display_name' => 'Menu Inventory', 'group' => 'inventory', 'type' => 'web'],
        ['name' => 'menu.cabang', 'display_name' => 'Menu Cabang', 'group' => 'cabang', 'type' => 'web'],
        ['name' => 'menu.users', 'display_name' => 'Menu Users', 'group' => 'users', 'type' => 'web'],
        ['name' => 'menu.roles', 'display_name' => 'Menu Roles', 'group' => 'roles', 'type' => 'web'],

        // API Orders
        ['name' => 'api.orders.index', 'display_name' => 'List Order', 'group' => 'orders', 'type' => 'api'],
        ['name' => 'api.orders.store', 'display_name' => 'Buat Order', 'group' => 'orders', 'type' => 'api'],
        ['name' => 'api.orders.show', 'display_name' => 'Detail Order', 'group' => 'orders', 'type' => 'api'],
        ['name' => 'api.orders.update', 'display_name' => 'Update Order', 'group' => 'orders', 'type' => 'api'],
        ['name' => 'api.orders.destroy', 'display_name' => 'Hapus Order', 'group' => 'orders', 'type' => 'api'],

        // API Payments
        ['name' => 'api.payments.index', 'display_name' => 'List Pembayaran', 'group' => 'payments', 'type' => 'api'],
        ['name' => 'api.payments.store', 'display_name' => 'Buat Pembayaran', 'group' => 'payments', 'type' => 'api'],
        ['name' => 'api.payments.show', 'display_name' => 'Detail Pembayaran', 'group' => 'payments', 'type' => 'api'],

        // API Products
        ['name' => 'api.products.index', 'display_name' => 'List Produk', 'group' => 'products', 'type' => 'api'],
        ['name' => 'api.products.store', 'display_name' => 'Tambah Produk', 'group' => 'products', 'type' => 'api'],
        ['name' => 'api.products.show', 'display_name' => 'Detail Produk', 'group' => 'products', 'type' => 'api'],
        ['name' => 'api.products.update', 'display_name' => 'Update Produk', 'group' => 'products', 'type' => 'api'],
        ['name' => 'api.products.destroy', 'display_name' => 'Hapus Produk', 'group' => 'products', 'type' => 'api'],

        // API Categories
        ['name' => 'api.categories.index', 'display_name' => 'List Kategori', 'group' => 'categories', 'type' => 'api'],
        ['name' => 'api.categories.store', 'display_name' => 'Tambah Kategori', 'group' => 'categories', 'type' => 'api'],
        ['name' => 'api.categories.show', 'display_name' => 'Detail Kategori', 'group' => 'categories', 'type' => 'api'],
        ['name' => 'api.categories.update', 'display_name' => 'Update Kategori', 'group' => 'categories', 'type' => 'api'],
        ['name' => 'api.categories.destroy', 'display_name' => 'Hapus Kategori', 'group' => 'categories', 'type' => 'api'],

        // API Groups
        ['name' => 'api.groups.index', 'display_name' => 'List Grup', 'group' => 'groups', 'type' => 'api'],
        ['name' => 'api.groups.store', 'display_name' => 'Tambah Grup', 'group' => 'groups', 'type' => 'api'],
        ['name' => 'api.groups.show', 'display_name' => 'Detail Grup', 'group' => 'groups', 'type' => 'api'],
        ['name' => 'api.groups.update', 'display_name' => 'Update Grup', 'group' => 'groups', 'type' => 'api'],
        ['name' => 'api.groups.destroy', 'display_name' => 'Hapus Grup', 'group' => 'groups', 'type' => 'api'],

        // API Inventory
        ['name' => 'api.inventory.index', 'display_name' => 'List Inventory', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.store', 'display_name' => 'Tambah Inventory', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.show', 'display_name' => 'Detail Inventory', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.update', 'display_name' => 'Update Inventory', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.destroy', 'display_name' => 'Hapus Inventory', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.adjust', 'display_name' => 'Adjust Stok', 'group' => 'inventory', 'type' => 'api'],
        ['name' => 'api.inventory.history', 'display_name' => 'Riwayat Stok', 'group' => 'inventory', 'type' => 'api'],

        // API Cabang
        ['name' => 'api.cabang.index', 'display_name' => 'List Cabang', 'group' => 'cabang', 'type' => 'api'],
        ['name' => 'api.cabang.store', 'display_name' => 'Tambah Cabang', 'group' => 'cabang', 'type' => 'api'],
        ['name' => 'api.cabang.show', 'display_name' => 'Detail Cabang', 'group' => 'cabang', 'type' => 'api'],
        ['name' => 'api.cabang.update', 'display_name' => 'Update Cabang', 'group' => 'cabang', 'type' => 'api'],
        ['name' => 'api.cabang.destroy', 'display_name' => 'Hapus Cabang', 'group' => 'cabang', 'type' => 'api'],

        // API Tables
        ['name' => 'api.tables.index', 'display_name' => 'List Meja', 'group' => 'tables', 'type' => 'api'],
        ['name' => 'api.tables.store', 'display_name' => 'Tambah Meja', 'group' => 'tables', 'type' => 'api'],
        ['name' => 'api.tables.show', 'display_name' => 'Detail Meja', 'group' => 'tables', 'type' => 'api'],
        ['name' => 'api.tables.update', 'display_name' => 'Update Meja', 'group' => 'tables', 'type' => 'api'],
        ['name' => 'api.tables.destroy', 'display_name' => 'Hapus Meja', 'group' => 'tables', 'type' => 'api'],
        ['name' => 'api.tables.statusAll', 'display_name' => 'Status Semua Meja', 'group' => 'tables', 'type' => 'api'],

        // API Reservations
        ['name' => 'api.reservations.index', 'display_name' => 'List Reservasi', 'group' => 'reservations', 'type' => 'api'],
        ['name' => 'api.reservations.store', 'display_name' => 'Tambah Reservasi', 'group' => 'reservations', 'type' => 'api'],
        ['name' => 'api.reservations.show', 'display_name' => 'Detail Reservasi', 'group' => 'reservations', 'type' => 'api'],
        ['name' => 'api.reservations.update', 'display_name' => 'Update Reservasi', 'group' => 'reservations', 'type' => 'api'],
        ['name' => 'api.reservations.destroy', 'display_name' => 'Hapus Reservasi', 'group' => 'reservations', 'type' => 'api'],

        // API Shift
        ['name' => 'api.shift.store', 'display_name' => 'Buka Shift', 'group' => 'shift', 'type' => 'api'],
        ['name' => 'api.shift.close', 'display_name' => 'Tutup Shift', 'group' => 'shift', 'type' => 'api'],
        ['name' => 'api.shift.active', 'display_name' => 'Shift Aktif', 'group' => 'shift', 'type' => 'api'],
        ['name' => 'api.shift.show', 'display_name' => 'Detail Shift', 'group' => 'shift', 'type' => 'api'],
        ['name' => 'api.shift.index', 'display_name' => 'List Shift', 'group' => 'shift', 'type' => 'api'],

        // API Roles
        ['name' => 'api.roles.index', 'display_name' => 'List Role', 'group' => 'roles', 'type' => 'api'],
        ['name' => 'api.roles.store', 'display_name' => 'Tambah Role', 'group' => 'roles', 'type' => 'api'],
        ['name' => 'api.roles.show', 'display_name' => 'Detail Role', 'group' => 'roles', 'type' => 'api'],
        ['name' => 'api.roles.update', 'display_name' => 'Update Role', 'group' => 'roles', 'type' => 'api'],
        ['name' => 'api.roles.destroy', 'display_name' => 'Hapus Role', 'group' => 'roles', 'type' => 'api'],

        // API Users
        ['name' => 'api.users.index', 'display_name' => 'List User', 'group' => 'users', 'type' => 'api'],
        ['name' => 'api.users.store', 'display_name' => 'Tambah User', 'group' => 'users', 'type' => 'api'],
        ['name' => 'api.users.show', 'display_name' => 'Detail User', 'group' => 'users', 'type' => 'api'],
        ['name' => 'api.users.update', 'display_name' => 'Update User', 'group' => 'users', 'type' => 'api'],
        ['name' => 'api.users.destroy', 'display_name' => 'Hapus User', 'group' => 'users', 'type' => 'api'],
    ];

    private array $rolePermissions = [
        'Superadmin' => '*',
        'Owner' => [
            'menu.dashboard', 'menu.reports', 'menu.exports',
        ],
        'Manager' => [
            'menu.dashboard', 'menu.reservation', 'menu.tables', 'menu.shift',
            'menu.reports', 'menu.exports', 'menu.catalog', 'menu.inventory',
            'menu.cabang', 'menu.users', 'menu.roles',
            'api.products.index', 'api.products.show',
            'api.categories.index', 'api.categories.show',
            'api.groups.index', 'api.groups.show',
            'api.inventory.index', 'api.inventory.show', 'api.inventory.adjust', 'api.inventory.history',
            'api.cabang.index', 'api.cabang.show',
            'api.tables.index', 'api.tables.show',
            'api.reservations.index', 'api.reservations.show', 'api.reservations.store', 'api.reservations.update',
            'api.shift.store', 'api.shift.close', 'api.shift.active', 'api.shift.show', 'api.shift.index',
        ],
        'Cashier' => [
            'api.orders.index', 'api.orders.store', 'api.orders.show', 'api.orders.update',
            'api.payments.index', 'api.payments.store', 'api.payments.show',
            'api.products.index', 'api.products.show',
            'api.categories.index', 'api.categories.show',
            'api.groups.index', 'api.groups.show',
            'api.tables.index', 'api.tables.show', 'api.tables.statusAll',
            'api.reservations.index', 'api.reservations.show', 'api.reservations.store',
            'api.shift.store', 'api.shift.close', 'api.shift.active',
        ],
        'Users' => [],
    ];

    public function run(): void
    {
        $created = [];
        foreach ($this->permissions as $item) {
            $perm = Permission::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'guid' => (string) Str::uuid(),
                    'display_name' => $item['display_name'],
                    'group' => $item['group'],
                    'type' => $item['type'],
                ],
            );
            $created[$item['name']] = $perm;
        }

        $roles = AuthenticationRole::all()->keyBy('name');

        foreach ($this->rolePermissions as $roleName => $perms) {
            $role = $roles->get($roleName);
            if (! $role) continue;

            $role->permissions()->detach();

            if ($perms === '*') {
                $role->permissions()->attach(
                    collect($created)->pluck('guid')->toArray()
                );
            } elseif (! empty($perms)) {
                $guids = collect($perms)
                    ->map(fn (string $name) => $created[$name]->guid ?? null)
                    ->filter()
                    ->toArray();
                $role->permissions()->attach($guids);
            }
        }
    }
}
