<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    shift: { type: Object, required: true },
    serverTime: { type: String, default: '' },
});

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const balanceCards = computed(() => [
    { label: 'Opening Balance', value: formatCurrency(props.shift.opening_balance), icon: 'savings' },
    { label: 'Expected Balance', value: formatCurrency(props.shift.expected_balance), icon: 'account_balance_wallet' },
    { label: 'Closing Balance', value: props.shift.closing_balance === null ? '-' : formatCurrency(props.shift.closing_balance), icon: 'payments' },
    { label: 'Difference', value: props.shift.difference === null ? '-' : formatCurrency(props.shift.difference), icon: 'compare_arrows' },
]);

const salesCards = computed(() => [
    { label: 'Total Sales', value: formatCurrency(props.shift.summary?.total_sales), icon: 'monitoring' },
    { label: 'Cash Sales', value: formatCurrency(props.shift.summary?.cash_sales), icon: 'payments' },
    { label: 'Digital Sales', value: formatCurrency(props.shift.summary?.digital_sales), icon: 'credit_card' },
    { label: 'Orders', value: props.shift.summary?.order_count ?? 0, icon: 'receipt_long' },
]);
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <Link class="back-link" href="/shifts">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Shift
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>{{ shift.shift_number }}</h1>
                    <p>{{ shift.user?.full_name }} · {{ shift.user?.role }} · {{ Number(shift.work_hours ?? 0).toFixed(2) }} jam kerja</p>
                </div>
                <span class="status" :class="`status--${shift.status}`">{{ shift.status }}</span>
            </section>

            <section class="hero">
                <div>
                    <span>Opened</span>
                    <strong>{{ formatDate(shift.opened_at) }}</strong>
                </div>
                <div>
                    <span>Closed</span>
                    <strong>{{ formatDate(shift.closed_at) }}</strong>
                </div>
                <div>
                    <span>Cashier</span>
                    <strong>{{ shift.user?.username }}</strong>
                </div>
            </section>

            <section class="kpi-grid">
                <article v-for="card in salesCards" :key="card.label" class="kpi-card">
                    <div class="kpi-card__icon">
                        <span class="material-symbols-outlined">{{ card.icon }}</span>
                    </div>
                    <span>{{ card.label }}</span>
                    <strong>{{ card.value }}</strong>
                </article>
            </section>

            <section class="kpi-grid">
                <article v-for="card in balanceCards" :key="card.label" class="kpi-card">
                    <div class="kpi-card__icon kpi-card__icon--balance">
                        <span class="material-symbols-outlined">{{ card.icon }}</span>
                    </div>
                    <span>{{ card.label }}</span>
                    <strong>{{ card.value }}</strong>
                </article>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div>
                        <h2>Orders Dalam Shift</h2>
                        <p>{{ shift.orders?.length ?? 0 }} order terbaru</p>
                    </div>
                </div>

                <div class="order-table">
                    <div class="order-row order-row--head">
                        <span>Order</span>
                        <span>Customer</span>
                        <span>Payment</span>
                        <span>Total</span>
                        <span>Paid</span>
                        <span>Time</span>
                    </div>

                    <div v-for="order in shift.orders" :key="order.guid" class="order-row">
                        <strong>{{ order.order_number }}</strong>
                        <span>{{ order.customer_name ?? 'Walk-in' }}</span>
                        <span>{{ order.payment_status }}</span>
                        <span>{{ formatCurrency(order.total_amount) }}</span>
                        <span>{{ formatCurrency(order.paid_amount) }}</span>
                        <span>{{ formatDate(order.ordered_at) }}</span>
                    </div>

                    <div v-if="!shift.orders?.length" class="empty-state">
                        Belum ada order yang terhubung ke shift ini.
                    </div>
                </div>
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

.dashboard-shell {
    min-height: 100vh;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.content {
    height: 100vh;
    overflow-y: auto;
    padding: 112px 32px 32px 112px;
}

.brand {
    font-size: 20px;
    font-weight: 800;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    border: 1px solid #d6d7de;
    border-radius: 8px;
    padding: 0 12px;
    color: #191c1d;
    font-weight: 800;
    text-decoration: none;
}

.page-title {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.page-title h1,
.panel h2 {
    margin: 0;
}

.page-title h1 {
    font-size: 32px;
    line-height: 40px;
}

.page-title p,
.panel p {
    margin: 4px 0 0;
    color: #5d6268;
    font-size: 13px;
}

.status {
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.status--open {
    background: #fff7d6;
    color: #8a5a00;
}

.status--closed {
    background: #dcfce7;
    color: #166534;
}

.hero {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 20px;
    border: 1px solid #dfe1e5;
    border-radius: 8px;
    background: #ffffff;
    padding: 18px;
}

.hero span,
.kpi-card span,
.order-row--head {
    color: #5d6268;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.hero strong {
    display: block;
    margin-top: 6px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.kpi-card,
.panel {
    border: 1px solid #dfe1e5;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgb(15 23 42 / 6%);
}

.kpi-card {
    min-height: 128px;
    padding: 18px;
}

.kpi-card__icon {
    display: grid;
    width: 40px;
    height: 40px;
    margin-bottom: 14px;
    place-items: center;
    border-radius: 8px;
    background: #eef2ff;
    color: #1a237e;
}

.kpi-card__icon--balance {
    background: #e9fbef;
    color: #166534;
}

.kpi-card strong {
    display: block;
    margin-top: 6px;
    font-size: 21px;
}

.panel {
    padding: 18px;
}

.panel__header {
    margin-bottom: 16px;
}

.order-table {
    display: grid;
    gap: 8px;
}

.order-row {
    display: grid;
    grid-template-columns: 1.2fr 1fr 0.8fr 1fr 1fr 1.1fr;
    align-items: center;
    gap: 12px;
    min-height: 56px;
    border: 1px solid #edf0f2;
    border-radius: 8px;
    padding: 12px 14px;
}

.order-row--head {
    min-height: 36px;
    border: 0;
}

.empty-state {
    padding: 32px;
    color: #5d6268;
    text-align: center;
}

@media (max-width: 980px) {
    .content {
        padding: 96px 16px 96px;
    }

    .page-title {
        align-items: stretch;
        flex-direction: column;
    }

    .hero,
    .kpi-grid,
    .order-row {
        grid-template-columns: 1fr;
    }

    .order-row--head {
        display: none;
    }
}
</style>
