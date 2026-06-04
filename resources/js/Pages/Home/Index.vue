<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    dashboard: {
        type: Object,
        default: () => ({
            sales_total: 0,
            cash_total: 0,
            digital_total: 0,
            transactions_today: 0,
            active_shift: '00:00:00',
            pending_payments: 0,
            completed_orders: 0,
            hourly_sales: [],
            recent_orders: [],
        }),
    },
    serverTime: {
        type: String,
        default: () => '',
    },
});

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const kpis = computed(() => [
    { label: 'Transactions Today', value: props.dashboard.transactions_today, icon: 'receipt_long' },
    { label: 'Active Shift', value: props.dashboard.active_shift, icon: 'timer' },
    { label: 'Printer Status', value: 'Epson TM-T82X', icon: 'print', badge: 'Online', positive: true },
    { label: 'Pending Payment', value: `${props.dashboard.pending_payments} Orders`, icon: 'payments', badge: `${props.dashboard.completed_orders} done` },
]);

const reportMenus = [
    { title: 'Laporan Penjualan', description: 'Ringkasan omzet, transaksi, dan metode bayar.', icon: 'monitoring', href: '/reports?type=sales' },
    { title: 'Laporan Katalog', description: 'Performa kasir, shift, void, dan refund.', icon: 'badge', href: '/reports?type=catalog' },
    { title: 'Laporan Keuangan', description: 'Cash flow harian, pajak, diskon, dan settlement.', icon: 'payments', href: '/reports?type=financial' },
    { title: 'Laporan Produk', description: 'Produk terlaris, stok menipis, dan kategori aktif.', icon: 'inventory', href: '/reports?type=products' },
];

const hourlySales = computed(() => props.dashboard.hourly_sales ?? []);
const orders = computed(() => props.dashboard.recent_orders ?? []);
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" value="" placeholder="Search orders, items..." />
                </label>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Overview</h1>
                    <p>Monitor performa hari ini dan akses laporan operasional POS.</p>
                </div>
                <!-- <Link class="primary-action" href="/orders">
                    <span class="material-symbols-outlined fill">add_shopping_cart</span>
                    Transaksi Baru
                </Link> -->
            </section>

            <section class="hero-grid" aria-label="Sales summary">
                <article class="sales-hero">
                    <div class="sales-hero__top">
                        <div>
                            <h2>
                                <span class="material-symbols-outlined">account_balance_wallet</span>
                                Today's Sales
                            </h2>
                            <div class="sales-hero__amount">{{ formatCurrency(dashboard.sales_total) }}</div>
                        </div>
                        <div class="trend-pill">
                            <span class="material-symbols-outlined">receipt_long</span>
                            {{ dashboard.transactions_today }} trx
                        </div>
                    </div>
                    <div class="sales-hero__split">
                        <div>
                            <span>Cash</span>
                            <strong>{{ formatCurrency(dashboard.cash_total) }}</strong>
                        </div>
                        <div>
                            <span>Card/QRIS/Digital</span>
                            <strong>{{ formatCurrency(dashboard.digital_total) }}</strong>
                        </div>
                    </div>
                </article>

                <Link class="quick-sale-card" href="/reports">
                    <div>
                        <span class="material-symbols-outlined fill">bar_chart</span>
                    </div>
                    <strong>Lihat Report</strong>
                </Link>
            </section>

            <section class="kpi-grid" aria-label="Operational indicators">
                <article v-for="kpi in kpis" :key="kpi.label" class="kpi-card">
                    <div class="kpi-card__top">
                        <div class="kpi-card__icon" :class="{ 'kpi-card__icon--positive': kpi.positive }">
                            <span class="material-symbols-outlined">{{ kpi.icon }}</span>
                        </div>
                        <span v-if="kpi.badge" class="status-badge" :class="{ 'status-badge--positive': kpi.positive }">
                            {{ kpi.badge }}
                        </span>
                    </div>
                    <span>{{ kpi.label }}</span>
                    <strong>{{ kpi.value }}</strong>
                </article>
            </section>

            <section class="report-grid" aria-label="Report menu">
                <article v-for="menu in reportMenus" :key="menu.title" class="report-card" @click="router.visit(menu.href)">
                    <div class="report-card__icon">
                        <span class="material-symbols-outlined">{{ menu.icon }}</span>
                    </div>
                    <div>
                        <h3>{{ menu.title }}</h3>
                        <p>{{ menu.description }}</p>
                    </div>
                    <span class="report-card__button">
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </span>
                </article>
            </section>

            <section class="bottom-grid">
                <article class="panel chart-panel">
                    <div class="panel__header">
                        <h3>Hourly Sales Trend</h3>
                        <button class="icon-button" aria-label="Chart options" type="button">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                    <div class="chart">
                        <div class="chart__axis">
                            <span>4M</span>
                            <span>3M</span>
                            <span>2M</span>
                            <span>1M</span>
                            <span>0</span>
                        </div>
                        <div class="chart__bars">
                            <div class="chart__guide chart__guide--one"></div>
                            <div class="chart__guide chart__guide--two"></div>
                            <div class="chart__guide chart__guide--three"></div>
                            <div
                                v-for="bar in hourlySales"
                                :key="bar.time"
                                class="chart__bar"
                                :class="`chart__bar--${bar.tone}`"
                                :style="{ height: bar.height }"
                            >
                                <span>{{ bar.time }}</span>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="panel orders-panel">
                    <div class="panel__header">
                        <h3>Recent Orders</h3>
                        <Link class="link-button" href="/orders">View All</Link>
                    </div>
                    <div class="order-list">
                        <div
                            v-for="order in orders"
                            :key="order.code"
                            class="order-row"
                            :class="{ 'order-row--voided': order.voided }"
                        >
                            <div class="order-row__left">
                                <div class="order-row__icon">
                                    <span class="material-symbols-outlined">{{ order.voided ? 'cancel' : 'receipt' }}</span>
                                </div>
                                <div>
                                    <strong>{{ order.code }}</strong>
                                    <span>{{ order.meta }}</span>
                                </div>
                            </div>
                            <div class="order-row__right">
                                <strong>{{ formatCurrency(order.amount) }}</strong>
                                <span>{{ order.status }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

:global(body) {
    margin: 0;
    overflow: hidden;
    background: #f8f9fa;
}

* {
    box-sizing: border-box;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}

.material-symbols-outlined.fill {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.dashboard-shell {
    min-height: 100vh;
    width: 100vw;
    overflow: hidden;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

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

.branch-card__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 11px;
    font-weight: 700;
    line-height: 16px;
}

.branch-card__terminal,
.nav-item span:last-child {
    font-size: 10px;
    font-weight: 700;
    line-height: 12px;
}

.branch-card__terminal {
    color: #454652;
}

.nav-list {
    gap: 8px;
    width: 100%;
}

button {
    border: 0;
    font: inherit;
    cursor: pointer;
}

.nav-item {
    display: flex;
    min-height: 54px;
    width: 56px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 8px;
    background: transparent;
    color: #454652;
    transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
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

.top-bar {
    position: fixed;
    top: 0;
    left: 80px;
    z-index: 50;
    display: flex;
    width: calc(100vw - 80px);
    height: 64px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #c6c5d4;
    background: #f8f9fa;
    padding: 0 16px;
}

.top-bar__left,
.top-bar__right,
.search-box,
.shift-pill,
.profile {
    display: flex;
    align-items: center;
}

.top-bar__left {
    gap: 24px;
    min-width: 0;
}

.top-bar__right {
    gap: 8px;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
    line-height: 40px;
}

.current-time {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ffffff;
    color: #454652;
    font-size: 14px;
    font-weight: 600;
}

.search-box {
    position: relative;
    width: 264px;
}

.search-box span {
    position: absolute;
    left: 12px;
    color: #454652;
}

.search-box input {
    width: 100%;
    height: 40px;
    border: 1px solid #c6c5d4;
    border-radius: 999px;
    background: #ffffff;
    padding: 0 16px 0 40px;
    color: #191c1d;
    outline: 0;
}

.icon-button {
    display: grid;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    place-items: center;
    border-radius: 999px;
    background: transparent;
    color: #454652;
    transition: background-color 160ms ease;
}

.icon-button:hover {
    background: #edeeef;
}

.top-bar__divider {
    width: 1px;
    height: 32px;
    margin: 0 8px;
    background: #c6c5d4;
}

.shift-pill {
    gap: 8px;
    border: 1px solid rgb(27 109 36 / 20%);
    border-radius: 999px;
    background: #a0f399;
    color: #217128;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.shift-pill span:first-child {
    font-size: 18px;
}

.profile {
    gap: 12px;
    padding-left: 8px;
}

.profile > div:first-child {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.profile strong {
    color: #000666;
    font-size: 14px;
    font-weight: 600;
}

.profile span {
    color: #454652;
    font-size: 10px;
    font-weight: 700;
}

.profile__avatar {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #c6c5d4;
    border-radius: 999px;
    background: #1a237e;
    color: #ffffff;
}

.content {
    height: calc(100vh - 64px);
    margin-left: 80px;
    margin-top: 64px;
    overflow-y: auto;
    padding: 24px;
}

.content::-webkit-scrollbar {
    width: 6px;
}

.content::-webkit-scrollbar-thumb {
    border-radius: 4px;
    background: #e1e3e4;
}

.page-title,
.hero-grid,
.kpi-grid,
.report-grid,
.bottom-grid {
    width: min(100%, 1280px);
    margin-inline: auto;
}

.page-title {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.page-title h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    line-height: 40px;
}

.page-title p {
    margin: 2px 0 0;
    color: #454652;
}

.primary-action {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    gap: 8px;
    border-radius: 8px;
    background: #1b6d24;
    color: #ffffff;
    padding: 0 16px;
    font-weight: 700;
    text-decoration: none;
}

.hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(240px, 1fr);
    gap: 12px;
    margin-bottom: 12px;
}

.sales-hero,
.quick-sale-card,
.kpi-card,
.report-card,
.panel {
    border-radius: 12px;
}

.sales-hero {
    position: relative;
    min-height: 180px;
    overflow: hidden;
    background: #000666;
    color: #ffffff;
    padding: 24px;
    box-shadow: 0 1px 3px rgb(15 23 42 / 12%);
}

.sales-hero__top {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.sales-hero h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    color: #bdc2ff;
    font-size: 18px;
    font-weight: 600;
}

.sales-hero__amount {
    font-size: 48px;
    font-weight: 800;
    line-height: 56px;
}

.trend-pill {
    display: flex;
    height: 32px;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    background: rgb(255 255 255 / 12%);
    padding: 0 12px;
    font-weight: 700;
}

.trend-pill span {
    font-size: 16px;
}

.sales-hero__split {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 24px;
    margin-top: 26px;
}

.sales-hero__split div + div {
    border-left: 1px solid rgb(255 255 255 / 22%);
    padding-left: 24px;
}

.sales-hero__split span {
    display: block;
    color: #bdc2ff;
    font-size: 14px;
}

.sales-hero__split strong {
    font-weight: 700;
}

.quick-sale-card {
    display: flex;
    min-height: 180px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 1px solid rgb(27 109 36 / 20%);
    background: #1b6d24;
    color: #ffffff;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 1px 3px rgb(15 23 42 / 12%);
}

.quick-sale-card div {
    display: grid;
    width: 64px;
    height: 64px;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 255 255 / 20%);
}

.quick-sale-card span {
    font-size: 34px;
}

.quick-sale-card strong {
    font-size: 24px;
    font-weight: 700;
    line-height: 30px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.kpi-card,
.report-card,
.panel {
    border: 1px solid #c6c5d4;
    background: #ffffff;
}

.kpi-card {
    display: flex;
    min-height: 150px;
    flex-direction: column;
    justify-content: center;
    padding: 16px;
}

.kpi-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

.kpi-card__icon,
.report-card__icon,
.order-row__icon {
    display: grid;
    place-items: center;
    background: #edeeef;
    color: #454652;
}

.kpi-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 999px;
}

.kpi-card__icon--positive {
    background: #a0f399;
    color: #217128;
}

.status-badge {
    border-radius: 999px;
    background: #e7e8e9;
    color: #454652;
    padding: 5px 8px;
    font-size: 12px;
    font-weight: 800;
}

.status-badge--positive {
    background: #a0f399;
    color: #217128;
}

.kpi-card > span {
    color: #454652;
    font-size: 15px;
}

.kpi-card > strong {
    margin-top: 2px;
    font-size: 28px;
    font-weight: 700;
    line-height: 34px;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.report-card {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) 36px;
    align-items: center;
    gap: 12px;
    min-height: 118px;
    padding: 16px;
    cursor: pointer;
}

.report-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #e0e0ff;
    color: #343d96;
}

.report-card h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
}

.report-card p {
    margin: 4px 0 0;
    color: #454652;
    font-size: 13px;
    line-height: 18px;
}

.report-card__button {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border-radius: 999px;
    background: transparent;
    color: #000666;
}

.report-card__button:hover {
    background: #edeeef;
}

.bottom-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 12px;
    padding-bottom: 24px;
}

.panel {
    padding: 20px;
}

.panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.panel__header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
}

.chart-panel {
    min-height: 316px;
}

.chart {
    position: relative;
    display: flex;
    min-height: 230px;
    gap: 14px;
}

.chart__axis {
    display: flex;
    width: 28px;
    flex-direction: column;
    justify-content: space-between;
    padding: 4px 0 30px;
    color: #454652;
    font-size: 12px;
}

.chart__bars {
    position: relative;
    display: flex;
    flex: 1;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #c6c5d4;
    padding-bottom: 30px;
}

.chart__guide {
    position: absolute;
    left: 0;
    width: 100%;
    border-top: 1px dashed rgb(198 197 212 / 55%);
}

.chart__guide--one {
    top: 25%;
}

.chart__guide--two {
    top: 50%;
}

.chart__guide--three {
    top: 75%;
}

.chart__bar {
    position: relative;
    z-index: 1;
    width: clamp(18px, 7vw, 58px);
    min-height: 12px;
    border-radius: 4px 4px 0 0;
    transition: opacity 160ms ease, transform 160ms ease;
}

.chart__bar:hover {
    opacity: 0.86;
    transform: translateY(-2px);
}

.chart__bar--softest {
    background: rgb(0 6 102 / 20%);
}

.chart__bar--soft {
    background: rgb(0 6 102 / 30%);
}

.chart__bar--medium {
    background: rgb(0 6 102 / 60%);
}

.chart__bar--bold {
    background: rgb(0 6 102 / 80%);
}

.chart__bar--strong {
    background: #000666;
}

.chart__bar--muted {
    background: #e7e8e9;
}

.chart__bar span {
    position: absolute;
    bottom: -24px;
    left: 50%;
    color: #454652;
    font-size: 12px;
    transform: translateX(-50%);
    white-space: nowrap;
}

.link-button {
    background: transparent;
    color: #000666;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-decoration: none;
    text-transform: uppercase;
}

.link-button:hover {
    text-decoration: underline;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    padding: 12px;
}

.order-row:hover {
    background: #f8f9fa;
}

.order-row__left {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 12px;
}

.order-row__icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border-radius: 8px;
}

.order-row__left strong,
.order-row__right strong {
    display: block;
    color: #191c1d;
    font-size: 14px;
    font-weight: 800;
}

.order-row__left span,
.order-row__right span {
    display: block;
    color: #454652;
    font-size: 12px;
}

.order-row__right {
    text-align: right;
    white-space: nowrap;
}

.order-row__right span {
    color: #1b6d24;
}

.order-row--voided {
    opacity: 0.72;
}

.order-row--voided .order-row__icon {
    background: #ffdad6;
    color: #93000a;
}

.order-row--voided strong {
    text-decoration: line-through;
}

.order-row--voided .order-row__right span {
    color: #ba1a1a;
}

@media (max-width: 1180px) {
    .report-grid,
    .kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .search-box,
    .top-bar__divider,
    .profile > div:first-child {
        display: none;
    }

    .brand {
        font-size: 24px;
    }

    .hero-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    :global(body) {
        overflow: auto;
    }

    .dashboard-shell {
        overflow: visible;
    }

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

    .top-bar {
        left: 0;
        width: 100%;
    }

    .top-bar__right {
        gap: 2px;
    }

    .shift-pill {
        display: none;
    }

    .content {
        height: auto;
        min-height: calc(100vh - 64px);
        margin-left: 0;
        padding: 18px 14px 92px;
    }

    .page-title {
        align-items: flex-start;
        flex-direction: column;
    }

    .sales-hero__top,
    .sales-hero__split {
        flex-direction: column;
    }

    .sales-hero__amount {
        font-size: 34px;
        line-height: 42px;
    }

    .sales-hero__split div + div {
        border-left: 0;
        border-top: 1px solid rgb(255 255 255 / 22%);
        padding-left: 0;
        padding-top: 12px;
    }

    .kpi-grid,
    .report-grid {
        grid-template-columns: 1fr;
    }

    .chart__bars {
        gap: 8px;
    }

    .chart__bar span {
        font-size: 10px;
    }
}
</style>
