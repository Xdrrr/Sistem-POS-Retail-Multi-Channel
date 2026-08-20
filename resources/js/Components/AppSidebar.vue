<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Map each nav item to the permission required to see it.
// Items without a `permission` key are always visible (e.g. Settings).
const navItems = [
    { label: 'Home', icon: 'home', href: '/', permission: 'menu.dashboard' },
    { label: 'Orders', icon: 'receipt_long', href: '/orders', permission: 'menu.orders' },
    { label: 'Reservation', icon: 'event_seat', href: '/reservations', permission: 'menu.reservation' },
    { label: 'Tables', icon: 'grid_view', href: '/tables', permission: 'menu.tables' },
    { label: 'Shift', icon: 'calendar_today', href: '/shifts', permission: 'menu.shift' },
    { label: 'Reports', icon: 'bar_chart', href: '/reports', permission: 'menu.reports' },
    { label: 'Products', icon: 'inventory_2', href: '/catalog', permission: 'menu.catalog' },
    { label: 'Inventory', icon: 'warehouse', href: '/inventory', permission: 'menu.inventory' },
    { label: 'Cabang', icon: 'store', href: '/cabang', permission: 'menu.cabang' },
    { label: 'Users', icon: 'manage_accounts', href: '/users', permission: 'menu.users' },
    { label: 'Permissions', icon: 'manage_accounts', href: '/permissions', permission: 'menu.roles' },
    { label: 'Settings', icon: 'settings', href: '/settings/profile' },
];

const page = usePage();

const userPermissions = computed(() => {
    const perms = page.props.auth?.user?.permissions;
    return Array.isArray(perms) ? new Set(perms) : new Set();
});

const visibleNavItems = computed(() =>
    navItems.filter((item) => {
        if (!item.permission) return true;
        return userPermissions.value.has(item.permission);
    }),
);

const currentPath = computed(() => {
    const url = page.url || '/';
    return url.split('?')[0].replace(/\/$/, '') || '/';
});

const isActive = (item) => {
    if (item.activeWhen === null) return false;
    if (item.href === '#') return false;
    if (item.href === '/') return currentPath.value === '/';

    return currentPath.value === item.href || currentPath.value.startsWith(`${item.href}/`);
};
</script>

<template>
    <nav class="side-nav" aria-label="Main navigation">
        <div class="side-nav__main">
            <div class="branch-card">
                <div class="branch-card__icon">
                    <span class="material-symbols-outlined">storefront</span>
                </div>
                <div class="branch-card__name">Dasboard</div>
                <!-- <div class="branch-card__terminal">Terminal 01</div> -->
            </div>

            <div class="nav-list">
                <Link
                    v-for="item in visibleNavItems"
                    :key="item.label"
                    class="nav-item"
                    :class="{ 'nav-item--active': isActive(item) }"
                    :href="item.href"
                >
                    <span class="material-symbols-outlined" :class="{ fill: isActive(item) }">{{ item.icon }}</span>
                    <span v-html="item.label"></span>
                </Link>
            </div>
        </div>

        <button class="nav-item nav-item--footer" type="button">
            <span class="material-symbols-outlined">help</span>
            <span>Help</span>
        </button>
    </nav>
</template>

<style scoped>
.side-nav {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 40;
    display: flex;
    width: 80px;
    flex-direction: column;
    border-right: 1px solid #c6c5d4;
    background: #ffffff;
    padding: 24px 8px;
    overflow-y: auto;
    scroll-behavior: smooth;
}

.side-nav::-webkit-scrollbar { display: none; }
.side-nav { scrollbar-width: none; -ms-overflow-style: none; }

.side-nav__main {
    width: 100%;
}

.nav-list {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.branch-card {
    margin-bottom: 24px;
    width: 100%;
    text-align: center;
}

.branch-card__icon {
    display: grid;
    width: 48px;
    height: 48px;
    margin: 0 auto 8px;
    place-items: center;
    border-radius: 8px;
    background: #1a237e;
    color: #ffffff;
    box-shadow: 0 1px 3px rgb(15 23 42 / 14%);
}

.branch-card__icon span {
    font-size: 28px;
}

.branch-card__name,
.branch-card__terminal,
.nav-item span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 10px;
    font-weight: 700;
    line-height: 12px;
}

.branch-card__name {
    color: #191c1d;
    font-size: 11px;
    line-height: 16px;
}

.branch-card__terminal {
    color: #454652;
}

.nav-list {
    gap: 8px;
    width: 100%;
}

.nav-item {
    display: flex;
    min-height: 54px;
    width: 56px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #454652;
    font: inherit;
    text-decoration: none;
    transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
    cursor: pointer;
}

.nav-item:hover {
    background: #e7e8e9;
}

.nav-item--active {
    transform: scale(0.96);
    background: #a0f399;
    color: #217128;
}

.nav-item--footer {
    width: 100%;
}

@media (max-width: 720px) {
    .side-nav {
        inset: auto 0 0 0;
        width: 100%;
        height: 72px;
        flex-direction: row;
        padding: 8px 0;
        border-top: 1px solid #c6c5d4;
        border-right: 0;
        overflow-y: hidden;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }

    .side-nav::-webkit-scrollbar { display: none; }

    .branch-card,
    .nav-item--footer {
        display: none;
    }

    .side-nav__main {
        width: auto;
        display: flex;
        flex-direction: row;
        align-items: center;
        flex-shrink: 0;
        gap: 2px;
        padding: 0 4px;
    }

    .nav-list {
        flex-direction: row;
    }

    .nav-item {
        width: 58px;
        min-height: 48px;
        flex-shrink: 0;
    }
}
</style>
