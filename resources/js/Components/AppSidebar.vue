<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const navItems = [
    { label: 'Home', icon: 'home', href: '/' },
    // { label: 'New Sale', icon: 'add_shopping_cart', href: '/orders', activeWhen: null },
    // { label: 'Orders', icon: 'receipt_long', href: '/orders' },
    { label: 'Reservation', icon: 'event_seat', href: '/reservations' },
    { label: 'Tables', icon: 'grid_view', href: '/tables' },
    { label: 'Shift', icon: 'calendar_today', href: '/shifts' },
    { label: 'Reports', icon: 'bar_chart', href: '/reports' },
    { label: 'History <br> Export', icon: 'history', href: '/reports/exports' },
    { label: 'Products', icon: 'inventory_2', href: '/catalog' },
    { label: 'Inventory', icon: 'warehouse', href: '/inventory' },
    { label: 'Cabang', icon: 'store', href: '/cabang' },
    // { label: 'Sync Center', icon: 'sync', href: '#' },
    { label: 'Settings', icon: 'settings', href: '/settings/profile' },
];

const page = usePage();

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
                    v-for="item in navItems"
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
    justify-content: space-between;
    border-right: 1px solid #c6c5d4;
    background: #ffffff;
    padding: 24px 8px;
}

.side-nav__main,
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
        padding: 8px;
        border-top: 1px solid #c6c5d4;
        border-right: 0;
    }

    .branch-card,
    .nav-item:nth-child(n + 6),
    .nav-item--footer {
        display: none;
    }

    .nav-list,
    .side-nav__main {
        flex: 1;
        flex-direction: row;
        justify-content: space-around;
    }

    .nav-item {
        width: 58px;
    }
}
</style>
